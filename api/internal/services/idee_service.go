package services

// Boîte à idées — logique métier : statuts, tri, permissions, archivage.
// Statuts jamais codés en dur ailleurs : handlers et vues utilisent ces constantes.

import (
	"sort"
	"time"
)

// ─── Statuts (énumération métier) ───────────────────────────────────────────
const (
	StatutIdeeEnAttente = "en_attente"
	StatutIdeeRealise   = "realise"
	StatutIdeeNonRetenu = "non_retenu"
)

// StatutsIdeeValides : statuts acceptés (source de vérité unique).
var StatutsIdeeValides = map[string]bool{
	StatutIdeeEnAttente: true,
	StatutIdeeRealise:   true,
	StatutIdeeNonRetenu: true,
}

// IsStatutIdeeValide : statut accepté ?
func IsStatutIdeeValide(statut string) bool {
	return StatutsIdeeValides[statut]
}

// ─── Tri ────────────────────────────────────────────────────────────────────
const (
	TriIdeePopulaire = "populaire"
	TriIdeeRecent    = "recent"
)

// IdeeTriable : minimum nécessaire au tri (découplé du modèle DB).
type IdeeTriable struct {
	ID              int
	NbVotes         int
	DatePublication time.Time
}

// TrierIdees trie en place. Tie-break déterministe :
//   - populaire (défaut) : votes desc, puis plus récente d'abord.
//   - recent : date desc, puis plus grand ID d'abord.
func TrierIdees(items []IdeeTriable, mode string) {
	switch mode {
	case TriIdeeRecent:
		sort.SliceStable(items, func(i, j int) bool {
			if items[i].DatePublication.Equal(items[j].DatePublication) {
				return items[i].ID > items[j].ID
			}
			return items[i].DatePublication.After(items[j].DatePublication)
		})
	default: // populaire
		sort.SliceStable(items, func(i, j int) bool {
			if items[i].NbVotes == items[j].NbVotes {
				return items[i].DatePublication.After(items[j].DatePublication)
			}
			return items[i].NbVotes > items[j].NbVotes
		})
	}
}

// NormaliserTri : mode de tri valide (défaut : populaire).
func NormaliserTri(mode string) string {
	if mode == TriIdeeRecent {
		return TriIdeeRecent
	}
	return TriIdeePopulaire
}

// ─── Permissions ─────────────────────────────────────────────────────────────
const RoleAdmin = "admin"

// PeutGererIdee : auteur ou admin peut gérer une idée (modif/statut/archivage/suppression).
func PeutGererIdee(role string, estAuteur bool) bool {
	return role == RoleAdmin || estAuteur
}

// ─── Exclusion des archivées du flux principal ───────────────────────────────

// EstDansFluxPrincipal : dans le flux (Populaire/Récent) seulement si non archivée.
func EstDansFluxPrincipal(archivedAt *time.Time) bool {
	return archivedAt == nil
}
