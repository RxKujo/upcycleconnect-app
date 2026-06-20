package services_test

import (
	"api/internal/services"
	"math"
	"testing"
)

func TestCO2EviteKg(t *testing.T) {
	cases := []struct {
		poids float64
		want  float64
	}{
		{0, 0},
		{100, 50},
		{33.6, 16.8},
		{1, 0.5},
	}
	for _, tc := range cases {
		got := services.CO2EviteKg(tc.poids)
		if math.Abs(got-tc.want) > 1e-9 {
			t.Errorf("CO2EviteKg(%v) = %v, want %v", tc.poids, got, tc.want)
		}
	}
}

func TestCO2EviteKg_RegulationFixe(t *testing.T) {
	// La règle métier est 50 % fixe — ce test documente le contrat.
	if services.CO2EviteKg(200) != 100 {
		t.Error("la règle CO2 doit rester 50 % du poids, indépendamment du matériau")
	}
}
