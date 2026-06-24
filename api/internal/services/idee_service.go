package services

// Boîte à idées — logique métier isolée et testable :
// cycle de vie (statuts), tri (popularité / récence), permissions par rôle
// et exclusion des idées archivées du flux principal.
//
// Aucune valeur de statut n'est codée en dur ailleurs : les handlers et les
// vues s'appuient sur ces constantes / helpers.

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

// StatutsIdeeValides liste les transitions autorisées (source de vérité unique).
var StatutsIdeeValides = map[string]bool{
	StatutIdeeEnAttente: true,
	StatutIdeeRealise:   true,
	StatutIdeeNonRetenu: true,
}

// IsStatutIdeeValide indique si une valeur de statut est acceptée.
func IsStatutIdeeValide(statut string) bool {
	return StatutsIdeeValides[statut]
}

// ─── Tri ────────────────────────────────────────────────────────────────────
const (
	TriIdeePopulaire = "populaire"
	TriIdeeRecent    = "recent"
)

// IdeeTriable expose le minimum nécessaire au tri (découplé du modèle DB).
type IdeeTriable struct {
	ID              int
	NbVotes         int
	DatePublication time.Time
}

// TrierIdees ordonne les idées selon le mode demandé, en place.
//
//   - TriIdeePopulaire (défaut) : score de votes décroissant ; à votes égaux,
//     la plus récente d'abord (tie-break déterministe).
//   - TriIdeeRecent : date de publication décroissante ; à date égale, l'ID le
//     plus grand d'abord.
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

// NormaliserTri renvoie un mode de tri valide (défaut : populaire).
func NormaliserTri(mode string) string {
	if mode == TriIdeeRecent {
		return TriIdeeRecent
	}
	return TriIdeePopulaire
}

// ─── Permissions ─────────────────────────────────────────────────────────────
const RoleAdmin = "admin"

// PeutGererIdee : un utilisateur peut modifier / changer le statut / archiver /
// supprimer une idée s'il en est l'auteur, OU s'il est administrateur.
// Centralise la règle utilisée par modification, statut, archivage, suppression.
func PeutGererIdee(role string, estAuteur bool) bool {
	return role == RoleAdmin || estAuteur
}

// ─── Exclusion des archivées du flux principal ───────────────────────────────

// EstDansFluxPrincipal : une idée est dans le flux (onglets Populaire/Récent)
// uniquement si elle n'est pas archivée.
func EstDansFluxPrincipal(archivedAt *time.Time) bool {
	return archivedAt == nil
}
