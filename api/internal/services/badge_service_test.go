package services_test

import (
	"testing"
)

// Les tests d'intégration badge (ComputeAndAwardBadges) nécessitent une DB.
// Les tests ci-dessous couvrent la logique pure de seuil, découplée de la DB.

// seuilAtteint reproduit la logique de badge_service sans DB.
func seuilAtteint(typeMateriau string, seuilObjets int, total int, parMateriau map[string]int) bool {
	if typeMateriau == "tous" {
		return total >= seuilObjets
	}
	return parMateriau[typeMateriau] >= seuilObjets
}

func TestSeuilBadgeGeneral(t *testing.T) {
	cases := []struct {
		total int
		seuil int
		want  bool
	}{
		{0, 1, false},
		{1, 1, true},
		{9, 10, false},
		{10, 10, true},
		{1000, 1000, true},
		{999, 1000, false},
	}
	for _, tc := range cases {
		got := seuilAtteint("tous", tc.seuil, tc.total, nil)
		if got != tc.want {
			t.Errorf("seuil(tous,%d,%d) = %v, want %v", tc.seuil, tc.total, got, tc.want)
		}
	}
}

func TestSeuilBadgeMateriau(t *testing.T) {
	par := map[string]int{"bois": 25, "metal": 5, "textile": 100}

	cases := []struct {
		mat   string
		seuil int
		want  bool
	}{
		{"bois", 20, true},   // intermédiaire atteint
		{"bois", 100, false}, // avancé non atteint
		{"metal", 20, false},
		{"textile", 100, true},
		{"plastique", 20, false}, // absent de la map
	}
	for _, tc := range cases {
		got := seuilAtteint(tc.mat, tc.seuil, 0, par)
		if got != tc.want {
			t.Errorf("seuil(%s,%d) = %v, want %v", tc.mat, tc.seuil, got, tc.want)
		}
	}
}
