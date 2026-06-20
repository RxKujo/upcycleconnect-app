package services_test

import "testing"

// wrrStep simule un tour de sélection WRR sans DB.
// candidats : liste de (id, poids, score_courant)
// Retourne l'id sélectionné et les scores mis à jour.
func wrrStep(ids []int, poids []int, scores []int64) (int, []int64) {
	updated := make([]int64, len(scores))
	copy(updated, scores)

	// Incrémenter tous les scores
	for i := range updated {
		updated[i] += int64(poids[i])
	}
	// Choisir le max
	best := 0
	for i := 1; i < len(updated); i++ {
		if updated[i] > updated[best] {
			best = i
		}
	}
	// Réinitialiser le gagnant
	updated[best] = 0
	return ids[best], updated
}

func TestWRRDistributionProportionnelle(t *testing.T) {
	// Deux pubs : poids 2 vs poids 1.
	// Sur 9 tours : pub 0 attendue 6 fois, pub 1 attendue 3 fois.
	ids    := []int{0, 1}
	poids  := []int{2, 1}
	scores := []int64{0, 0}

	count := make(map[int]int)
	for i := 0; i < 9; i++ {
		winner, newScores := wrrStep(ids, poids, scores)
		count[winner]++
		scores = newScores
	}

	if count[0] != 6 {
		t.Errorf("pub poids=2 : attendu 6 sélections, got %d", count[0])
	}
	if count[1] != 3 {
		t.Errorf("pub poids=1 : attendu 3 sélections, got %d", count[1])
	}
}

func TestWRRPoidEgaux(t *testing.T) {
	ids    := []int{0, 1, 2}
	poids  := []int{1, 1, 1}
	scores := []int64{0, 0, 0}

	count := make(map[int]int)
	for i := 0; i < 6; i++ {
		winner, newScores := wrrStep(ids, poids, scores)
		count[winner]++
		scores = newScores
	}

	for _, id := range ids {
		if count[id] != 2 {
			t.Errorf("pub %d : attendu 2 sélections avec poids égaux, got %d", id, count[id])
		}
	}
}

func TestWRRSingleCandidat(t *testing.T) {
	ids    := []int{42}
	poids  := []int{5}
	scores := []int64{0}

	for i := 0; i < 3; i++ {
		winner, newScores := wrrStep(ids, poids, scores)
		if winner != 42 {
			t.Errorf("unique candidat doit toujours gagner")
		}
		if newScores[0] != 0 {
			t.Errorf("score doit être remis à 0 après sélection")
		}
		scores = newScores
	}
}
