package handlers

import "testing"

func TestIsValidEmail(t *testing.T) {
	cases := []struct {
		email string
		want  bool
	}{
		{"jean.dupont@example.com", true},
		{"a@b.co", true},
		{"USER@Domaine.FR", true},
		{"", false},
		{"pasdarobase.com", false},
		{"deux@@arobases.com", false},
		{"sans@domaine", false},
		{"espace dans@mail.com", false},
		{"@nodomain.com", false},
		{"trailing@space.com ", true}, // les espaces de bord sont normalisés (trim)
	}

	for _, c := range cases {
		if got := isValidEmail(c.email); got != c.want {
			t.Errorf("isValidEmail(%q) = %v, attendu %v", c.email, got, c.want)
		}
	}
}
