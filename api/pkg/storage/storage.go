// Package storage abstrait le stockage des médias (photos d'annonces, avatars,
// photos de conteneurs). La "clé" manipulée par les handlers est le chemin
// relatif stocké en base dans url_photo / photo_profil_url (ex : "photos/uuid.jpg").
// Ce même chemin est ensuite préfixé côté front par MEDIA_URL pour l'affichage.
//
// Deux implémentations, choisies via la variable d'environnement STORAGE_DRIVER :
//   - "local" (défaut) : écriture sur le système de fichiers (dossier UPLOAD_ROOT).
//   - "s3"             : upload vers un bucket S3-compatible (MinIO en dev,
//     fournisseur S3 en prod).
package storage

import (
	"bytes"
	"context"
	"fmt"
	"log"
	"mime"
	"os"
	"path/filepath"
	"strings"
	"sync"

	"github.com/aws/aws-sdk-go-v2/aws"
	awsconfig "github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/credentials"
	"github.com/aws/aws-sdk-go-v2/service/s3"
)

// Storage est le contrat commun aux backends de stockage.
type Storage interface {
	// Save écrit data sous la clé donnée (ex : "photos/uuid.jpg").
	Save(key string, data []byte, contentType string) error
	// Delete supprime l'objet ; l'absence de l'objet n'est pas une erreur.
	Delete(key string) error
}

var (
	instance Storage
	once     sync.Once
)

// Default renvoie l'instance de stockage partagée (initialisée à la première
// utilisation, à partir des variables d'environnement).
func Default() Storage {
	once.Do(func() { instance = build() })
	return instance
}

// imageTypes couvre les extensions image que le paquet mime standard ne connaît
// pas toujours (webp notamment), pour servir un Content-Type correct depuis S3.
var imageTypes = map[string]string{
	".jpg":  "image/jpeg",
	".jpeg": "image/jpeg",
	".png":  "image/png",
	".webp": "image/webp",
	".gif":  "image/gif",
	".svg":  "image/svg+xml",
}

// ContentType déduit le type MIME d'une clé d'après son extension.
func ContentType(key string) string {
	ext := strings.ToLower(filepath.Ext(key))
	if ct, ok := imageTypes[ext]; ok {
		return ct
	}
	if ct := mime.TypeByExtension(ext); ct != "" {
		return ct
	}
	return "application/octet-stream"
}

func build() Storage {
	if strings.EqualFold(os.Getenv("STORAGE_DRIVER"), "s3") {
		if s, err := newS3(); err == nil {
			log.Printf("[storage] backend S3 actif (bucket=%s)", os.Getenv("AWS_BUCKET"))
			return s
		} else {
			log.Printf("[storage] init S3 échouée, repli sur le stockage local: %v", err)
		}
	}
	root := uploadRoot()
	log.Printf("[storage] backend local actif (root=%s)", root)
	return &localStorage{root: root}
}

// uploadRoot renvoie la racine des uploads locaux. Historiquement UPLOAD_DIR
// pointe vers "<root>/photos" ; on remonte d'un cran pour obtenir la racine.
func uploadRoot() string {
	if r := os.Getenv("UPLOAD_ROOT"); r != "" {
		return r
	}
	if d := os.Getenv("UPLOAD_DIR"); d != "" {
		return filepath.Dir(d)
	}
	return "../web/public/uploads"
}

// ─── Backend local ────────────────────────────────────────────────────────────

type localStorage struct{ root string }

func (l *localStorage) Save(key string, data []byte, _ string) error {
	full := filepath.Join(l.root, filepath.FromSlash(key))
	if err := os.MkdirAll(filepath.Dir(full), 0o755); err != nil {
		return err
	}
	return os.WriteFile(full, data, 0o644)
}

func (l *localStorage) Delete(key string) error {
	full := filepath.Join(l.root, filepath.FromSlash(key))
	if err := os.Remove(full); err != nil && !os.IsNotExist(err) {
		return err
	}
	return nil
}

// ─── Backend S3 ───────────────────────────────────────────────────────────────

type s3Storage struct {
	client *s3.Client
	bucket string
}

func newS3() (Storage, error) {
	bucket := os.Getenv("AWS_BUCKET")
	if bucket == "" {
		return nil, fmt.Errorf("AWS_BUCKET manquant")
	}
	region := os.Getenv("AWS_DEFAULT_REGION")
	if region == "" {
		region = "us-east-1"
	}

	cfg, err := awsconfig.LoadDefaultConfig(context.Background(),
		awsconfig.WithRegion(region),
		awsconfig.WithCredentialsProvider(credentials.NewStaticCredentialsProvider(
			os.Getenv("AWS_ACCESS_KEY_ID"),
			os.Getenv("AWS_SECRET_ACCESS_KEY"),
			"",
		)),
	)
	if err != nil {
		return nil, err
	}

	endpoint := os.Getenv("AWS_ENDPOINT")
	pathStyle := strings.EqualFold(os.Getenv("AWS_USE_PATH_STYLE_ENDPOINT"), "true")
	client := s3.NewFromConfig(cfg, func(o *s3.Options) {
		if endpoint != "" {
			o.BaseEndpoint = aws.String(endpoint)
		}
		o.UsePathStyle = pathStyle
	})

	return &s3Storage{client: client, bucket: bucket}, nil
}

func (s *s3Storage) Save(key string, data []byte, contentType string) error {
	_, err := s.client.PutObject(context.Background(), &s3.PutObjectInput{
		Bucket:      aws.String(s.bucket),
		Key:         aws.String(key),
		Body:        bytes.NewReader(data),
		ContentType: aws.String(contentType),
	})
	return err
}

func (s *s3Storage) Delete(key string) error {
	_, err := s.client.DeleteObject(context.Background(), &s3.DeleteObjectInput{
		Bucket: aws.String(s.bucket),
		Key:    aws.String(key),
	})
	return err
}
