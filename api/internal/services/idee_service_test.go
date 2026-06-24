package services

import (
	"testing"
	"time"
)

func TestTrierIdeesPopulaire(t *testing.T) {
	base := time.Date(2026, 6, 1, 10, 0, 0, 0, time.UTC)
	items := []IdeeTriable{
		{ID: 1, NbVotes: 2, DatePublication: base},
		{ID: 2, NbVotes: 5, DatePublication: base},
		{ID: 3, NbVotes: 5, DatePublication: base.Add(time.Hour)}, // plus récent, votes égaux à 2
	}
	TrierIdees(items, TriIdeePopulaire)

	// Score décroissant : id 3 (5 votes, plus récent), puis id 2 (5 votes), puis id 1 (2 votes).
	if items[0].ID != 3 || items[1].ID != 2 || items[2].ID != 1 {
		t.Fatalf("ordre populaire inattendu : %d, %d, %d", items[0].ID, items[1].ID, items[2].ID)
	}
}

func TestTrierIdeesRecent(t *testing.T) {
	base := time.Date(2026, 6, 1, 10, 0, 0, 0, time.UTC)
	items := []IdeeTriable{
		{ID: 1, NbVotes: 100, DatePublication: base},                 // beaucoup de votes mais ancien
		{ID: 2, NbVotes: 0, DatePublication: base.Add(48 * time.Hour)}, // récent, 0 vote
		{ID: 3, NbVotes: 1, DatePublication: base.Add(24 * time.Hour)},
	}
	TrierIdees(items, TriIdeeRecent)

	// Date décroissante : id 2, id 3, id 1 — les votes n'entrent pas en jeu.
	if items[0].ID != 2 || items[1].ID != 3 || items[2].ID != 1 {
		t.Fatalf("ordre récent inattendu : %d, %d, %d", items[0].ID, items[1].ID, items[2].ID)
	}
}

func TestNormaliserTri(t *testing.T) {
	if NormaliserTri("recent") != TriIdeeRecent {
		t.Error("recent doit rester recent")
	}
	if NormaliserTri("populaire") != TriIdeePopulaire {
		t.Error("populaire doit rester populaire")
	}
	if NormaliserTri("") != TriIdeePopulaire {
		t.Error("vide doit retomber sur populaire (défaut)")
	}
	if NormaliserTri("n'importe quoi") != TriIdeePopulaire {
		t.Error("valeur inconnue doit retomber sur populaire")
	}
}

func TestIsStatutIdeeValide(t *testing.T) {
	for _, s := range []string{StatutIdeeEnAttente, StatutIdeeRealise, StatutIdeeNonRetenu} {
		if !IsStatutIdeeValide(s) {
			t.Errorf("%q devrait être valide", s)
		}
	}
	for _, s := range []string{"", "valide", "supprime", "EN_ATTENTE"} {
		if IsStatutIdeeValide(s) {
			t.Errorf("%q ne devrait pas être valide", s)
		}
	}
}

func TestPeutGererIdee(t *testing.T) {
	cases := []struct {
		role      string
		estAuteur bool
		attendu   bool
	}{
		{"admin", false, true},   // admin sur l'idée d'un autre
		{"admin", true, true},    // admin sur sa propre idée
		{"salarie", true, true},  // auteur
		{"salarie", false, false}, // salarié non-auteur : interdit
		{"particulier", false, false},
	}
	for _, c := range cases {
		if got := PeutGererIdee(c.role, c.estAuteur); got != c.attendu {
			t.Errorf("PeutGererIdee(%q, %v) = %v, attendu %v", c.role, c.estAuteur, got, c.attendu)
		}
	}
}

func TestEstDansFluxPrincipal(t *testing.T) {
	if !EstDansFluxPrincipal(nil) {
		t.Error("une idée non archivée doit être dans le flux principal")
	}
	now := time.Now()
	if EstDansFluxPrincipal(&now) {
		t.Error("une idée archivée doit être exclue du flux principal")
	}
}
