$urlBase = "http://localhost:8888/api"

# 1. Register a particulier
$registerBody = @{
    nom = "Testeur"
    prenom = "Particulier"
    email = "particulier@test.com"
    mot_de_passe = "password123"
    telephone = "0600000000"
    ville = "Paris"
    role = "particulier"
} | ConvertTo-Json

try {
    Invoke-RestMethod -Method Post -Uri "$urlBase/v1/auth/register" -Body $registerBody -ContentType "application/json"
} catch {
    Write-Host "Register skipped or failed (maybe already exists)"
}

# 2. Login to get token
$loginBody = @{
    email = "particulier@test.com"
    mot_de_passe = "password123"
} | ConvertTo-Json

$loginResponse = Invoke-RestMethod -Method Post -Uri "$urlBase/v1/auth/login" -Body $loginBody -ContentType "application/json"
$token = $loginResponse.Token

Write-Host "Token obtained: $token"

# 3. Create Annonce using token
$headers = @{
    Authorization = "Bearer $token"
}

$annonceBody = @{
    titre = "Superbe Lampe Vintage"
    description = "Trouvée dans un vieux grenier, idéale pour un salon rétro."
    type_annonce = "vente"
    prix = 45.00
    mode_remise = "conteneur"
} | ConvertTo-Json

$annonceResponse = Invoke-RestMethod -Method Post -Uri "$urlBase/v1/annonces" -Headers $headers -Body $annonceBody -ContentType "application/json"

Write-Host "Annonce Response:"
$annonceResponse | ConvertTo-Json
