# MIA POS Payment Gateway pentru WooCommerce

Acceptați plăți în magazinul dvs. WooCommerce folosind sistemul de plată MIA POS.

## Descriere

Acest plugin adaugă MIA POS ca metodă de plată în magazinul dvs. WooCommerce. MIA POS este un sistem de plată furnizat de Finergy Tech care vă permite să acceptați plăți prin coduri QR și cereri directe de plată.

### Caracteristici

- Acceptarea plăților prin coduri QR
- Suport pentru plăți Request to Pay (RTP)
- Actualizări automate ale stării comenzilor
- Procesare sigură a plăților
- Suport pentru mai multe limbi (RO, RU, EN)
- Mod de testare pentru dezvoltare și testare

## Cerințe

- WordPress 5.0 sau mai nou
- WooCommerce 4.0 sau mai nou
- PHP 7.2 sau mai nou
- Certificat SSL instalat
- Cont de comerciant MIA POS

## Instalare

1. Descărcați fișierul zip al plugin-ului
2. Accesați panoul de administrare WordPress > Plugin-uri > Adăugați nou
3. Faceți clic pe "Încărcați plugin" și selectați fișierul zip descărcat
4. Faceți clic pe "Instalați acum" și apoi pe "Activați"
5. Accesați WooCommerce > Setări > Plăți
6. Găsiți "MIA POS Payment" și faceți clic pe "Gestionați"
7. Configurați setările plugin-ului:
   - Introduceți ID-ul dvs. de comerciant
   - Introduceți cheia dvs. secretă
   - Introduceți ID-ul terminalului dvs.
   - Configurați alte setări opționale
8. Salvați modificările

## Configurare

### Setări necesare

- **Merchant ID**: Identificatorul dvs. unic de comerciant (furnizat de MIA POS)
- **Secret Key**: Cheia dvs. secretă pentru autentificare API (furnizată de MIA POS)
- **Terminal ID**: Identificatorul terminalului dvs. (furnizat de MIA POS)
- **API Base URL**: URL-ul endpoint-ului API MIA POS

### Setări opționale

- **Mod de testare**: Activați pentru testarea plăților
- **Tip de plată**: Alegeți între metodele de plată QR sau RTP
- **Limbă**: Selectați limba implicită a paginii de plată
- **Titlu**: Titlul metodei de plată afișat clienților
- **Descriere**: Descrierea metodei de plată afișată clienților

## Testare

1. Activați modul de testare în setările plugin-ului
2. Utilizați credențialele de test furnizate de MIA POS
3. Efectuați achiziții de test pentru a verifica fluxul de plată
4. Verificați actualizările stării comenzilor
5. Verificați gestionarea callback-urilor

## Suport

Pentru suport și întrebări, vă rugăm să contactați:
- Website: [https://finergy.md/](https://finergy.md/)
- Email: [info@finergy.md](mailto:info@finergy.md)
