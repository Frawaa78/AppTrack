# 🎨 FONT AWESOME PRO OPPSETT

## 📋 For å aktivere Font Awesome Pro ikoner:

### 🔑 **Trinn 1: Finn din Kit ID**
1. Gå til [fontawesome.com](https://fontawesome.com)
2. Logg inn med din Pro-konto
3. Gå til "Kits" i dashboardet
4. Kopier din Kit ID (ser ut som: `a1b2c3d4e5`)

### 🔧 **Trinn 2: Oppdater HTML**
Erstatt `your-kit-id` i disse filene:

**I `index-fontawesome-pro.html` (linje 7):**
```html
<!-- ENDRE DENNE LINJEN: -->
<script src="https://kit.fontawesome.com/your-kit-id.js" crossorigin="anonymous"></script>

<!-- TIL: -->
<script src="https://kit.fontawesome.com/a1b2c3d4e5.js" crossorigin="anonymous"></script>
```

### 🎯 **Trinn 3: Bruk riktig fil**
- **Med Pro:** Bruk `index-fontawesome-pro.html` (rename til `index.html`)
- **Uten Pro:** Bruk `index.html` (har Unicode fallback ikoner)

---

## 📁 FILER FOR ONE.COM:

### ✅ **Med Font Awesome Pro:**
1. `index-fontawesome-pro.html` (rename til `index.html`)
2. `styles.css`
3. `app.js`

### ✅ **Uten Font Awesome Pro (fallback):**
1. `index.html` (bruker Unicode ikoner)
2. `styles.css`
3. `app.js`

---

## 🎨 IKONER SOM BRUKES:

### 📌 **Font Awesome Pro (thin style):**
- **Info:** `<i class="fa-thin fa-circle-info"></i>`
- **Spørsmål:** `<i class="fa-thin fa-circle-question"></i>`

### 📌 **Unicode fallback:**
- **Info:** `ⓘ` (Unicode: U+24D8)
- **Spørsmål:** `?` (Unicode: U+003F)

---

## ⚙️ ENDRINGER GJORT:

✅ **Kortbredde:** 500px → 510px (+10px)
✅ **Knappetekst:** 1rem → 0.95rem (-5%)
✅ **Knappetykkelse:** 600 → 700 (bold)
✅ **Ikoner:** Styled med bakgrunn og border-radius

Begge versjoner fungerer perfekt på one.com! 🚀
