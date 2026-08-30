import puppeteer from "puppeteer";
import { mkdirSync } from "fs";
const B = "http://localhost:3000";
const OUT = "/tmp/claude-0/-home-user-client/1be3be1f-0159-5b29-a9b7-365c00094aa7/scratchpad/shots";
mkdirSync(OUT, { recursive: true });
const SLUG = "/partenaire/dr-amina-belkacem";
const PH = "/partenaire/pharmacie-el-chifa-ouled-maalah";

const b = await puppeteer.launch({ executablePath: "/opt/pw-browsers/chromium", args: ["--no-sandbox"] });

async function login(email) {
  const p = await (await b.createBrowserContext()).newPage();
  await p.setViewport({ width: 1440, height: 950, deviceScaleFactor: 2 });
  await p.goto(B + "/connexion", { waitUntil: "networkidle0" });
  await p.type("#email", email);
  await p.type("#password", "doctory-demo");
  await Promise.all([p.waitForNavigation({ waitUntil: "networkidle0" }), p.click('button[type="submit"]')]);
  return p;
}
const shoot = async (p, route, name, full = false) => {
  if (route) await p.goto(B + route, { waitUntil: "networkidle0" });
  await new Promise((r) => setTimeout(r, 250));
  await p.screenshot({ path: `${OUT}/${name}.png`, fullPage: full });
  console.log("  ", name);
};

// --- Public, desktop ---
const pub = await (await b.createBrowserContext()).newPage();
await pub.setViewport({ width: 1440, height: 950, deviceScaleFactor: 2 });
console.log("public");
await shoot(pub, "/", "01-accueil");
await shoot(pub, "/recherche?q=cardiologue&wilaya=16", "02-recherche");
await shoot(pub, "/recherche?categorie=pharmacy&ouvert=1", "03-pharmacies-ouvertes");
await shoot(pub, "/autour-de-moi?wilaya=16", "04-autour-de-moi");
await shoot(pub, SLUG, "05-fiche-praticien", true);
await shoot(pub, PH, "06-fiche-pharmacie", true);
await shoot(pub, SLUG + "/rendez-vous", "07-prise-rdv");
await shoot(pub, "/inscription", "08-inscription-patient");
await shoot(pub, "/inscription/professionnel", "09-inscription-pro-1");
await shoot(pub, "/inscription/professionnel?type=doctor&wilaya=16", "10-inscription-pro-2");
await shoot(pub, "/inscription/professionnel?type=doctor&wilaya=16&commune=1601", "11-inscription-pro-3", true);
await shoot(pub, "/connexion", "12-connexion");
await shoot(pub, "/partenaire/introuvable", "13-404");

// --- Patient ---
console.log("patient");
const pat = await login("patient1@exemple.dz");
await shoot(pat, "/patient", "20-patient-tableau");
await shoot(pat, "/patient/rendez-vous", "21-patient-rdv");
await shoot(pat, "/patient/favoris", "22-patient-favoris");
await shoot(pat, "/patient/notifications", "23-patient-notifications");

// --- Professional ---
console.log("pro");
const pro = await login("pro-dr-amina-belkacem@doctory.dz");
await shoot(pro, "/pro", "30-pro-tableau");
await shoot(pro, "/pro/agenda", "31-pro-agenda");
await shoot(pro, "/pro/horaires", "32-pro-horaires", true);
await shoot(pro, "/pro/profil", "33-pro-profil", true);
await shoot(pro, "/pro/abonnement", "34-pro-abonnement");

// --- Administration ---
console.log("admin");
const adm = await login("admin@doctory.dz");
await shoot(adm, "/admin", "40-admin-tableau", true);
await shoot(adm, "/admin/partenaires", "41-admin-partenaires");
await shoot(adm, "/admin/utilisateurs", "42-admin-utilisateurs");
await shoot(adm, "/admin/rendez-vous", "43-admin-rendez-vous");
await shoot(adm, "/admin/statistiques", "44-admin-statistiques", true);
await shoot(adm, "/admin/abonnements", "45-admin-abonnements");
await shoot(adm, "/admin/activite", "46-admin-activite");

// --- Mobile ---
console.log("mobile");
const mob = await (await b.createBrowserContext()).newPage();
await mob.setViewport({ width: 390, height: 844, deviceScaleFactor: 3 });
for (const [route, name] of [["/", "m1-accueil"], ["/recherche?q=cardiologue", "m2-recherche"],
  [SLUG + "/rendez-vous", "m3-rdv"], ["/autour-de-moi?wilaya=16", "m4-autour"], [SLUG, "m5-fiche"]]) {
  await shoot(mob, route, name);
}
await b.close();
