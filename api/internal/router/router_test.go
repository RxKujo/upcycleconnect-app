package router

import (
	"reflect"
	"testing"
)

func TestMatch(t *testing.T) {
	if !match("/api/v1/auth/login", "/api/v1/auth/login") {
		t.Error("match() devrait être vrai pour des chemins identiques")
	}
	if match("/api/v1/auth/login/", "/api/v1/auth/login") {
		t.Error("match() devrait être faux en cas de slash final supplémentaire")
	}
	if match("/api/v1/auth", "/api/v1/auth/login") {
		t.Error("match() devrait être faux pour un préfixe partiel")
	}
}

func TestSplitPath(t *testing.T) {
	cases := []struct {
		path   string
		prefix string
		want   []string
	}{
		{"/api/v1/annonces/42", "/api/v1/annonces", []string{"42"}},
		{"/api/v1/annonces/42/annuler", "/api/v1/annonces", []string{"42", "annuler"}},
		{"/api/v1/annonces", "/api/v1/annonces", []string{}},
		{"/api/v1/annonces/", "/api/v1/annonces", []string{}},
		{"/autre/chemin", "/api/v1/annonces", nil},
	}

	for _, c := range cases {
		got := splitPath(c.path, c.prefix)
		if !reflect.DeepEqual(got, c.want) {
			t.Errorf("splitPath(%q, %q) = %#v, attendu %#v", c.path, c.prefix, got, c.want)
		}
	}
}
