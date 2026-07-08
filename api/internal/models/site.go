// Fichier site.go : antennes physiques UpcycleConnect (sites/ateliers).

package models

// SiteUC : antenne physique UpcycleConnect (atelier/local). Un salarié peut y
// être rattaché (utilisateurs.id_site_uc) ; le matériel hérite du site de son
// créateur (materiels.id_site). Un salarié sans site voit tout l'inventaire.
type SiteUC struct {
	IDSite      int     `json:"id_site" db:"id_site"`
	NomSite     string  `json:"nom_site" db:"nom_site"`
	Adresse     *string `json:"adresse" db:"adresse"`
	Ville       *string `json:"ville" db:"ville"`
	CodePostal  *string `json:"code_postal" db:"code_postal"`
	NbSalaries  int     `json:"nb_salaries"`
	NbMateriels int     `json:"nb_materiels"`
}
