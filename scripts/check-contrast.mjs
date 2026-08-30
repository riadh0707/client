import { ratio } from "./contrast.mjs";
// Candidate scale: the Algerian enamel street plaque blue — deep, slightly
// cyan-leaning, not the generic SaaS trust-blue.
const C = {
  50:"#eff8fd", 100:"#d3ecfa", 200:"#a9dbf5", 300:"#6ec3ee",
  400:"#2ea6e0", 500:"#0092d8", 600:"#0072ad", 700:"#0a5075",
  800:"#093d5b", 900:"#082e44", 950:"#041a27",
};
const enamel = { 50:"#fbfcfd", 100:"#f2f6f9", 200:"#e5ecf1", 300:"#d2dde5" };
const ink = { 900:"#0d1c26", 800:"#16303e", 600:"#3f5462", 500:"#5a707e", 400:"#5e737f", 300:"#617682" };

const tests = [
  ["texte blanc sur champ 700", "#eff8fd", C[700], 4.5],
  ["enamel-50 sur champ 700", enamel[50], C[700], 4.5],
  ["blue-100 (sous-titre) sur 700", C[100], C[700], 4.5],
  ["encre du bouton sur 500", C[950], C[500], 4.5],
  ["blue-700 texte sur enamel-50", C[700], enamel[50], 4.5],
  ["blue-700 texte sur enamel-100", C[700], enamel[100], 4.5],
  ["blue-800 sur blue-100 (badge)", C[800], C[100], 4.5],
  ["ink-900 sur enamel-50", ink[900], enamel[50], 4.5],
  ["ink-500 sur enamel-50", ink[500], enamel[50], 4.5],
  ["ink-400 sur enamel-100", ink[400], enamel[100], 4.5],
  ["ink-300 sur blanc (placeholder)", ink[300], "#ffffff", 4.5],
  ["anneau focus ink-900 sur 500", ink[900], C[500], 3],
  ["anneau focus ink-900 sur enamel-50", ink[900], enamel[50], 3],
  ["anneau focus ink-900 sur blanc", ink[900], "#ffffff", 3],
  ["halo enamel-50 sur champ 700", enamel[50], C[700], 3],
];
let ko = 0;
for (const [nom, a, b, min] of tests) {
  const r = ratio(a, b);
  const ok = r >= min;
  if (!ok) ko++;
  console.log(`${ok ? "ok " : "KO "} ${r.toFixed(2).padStart(5)} (min ${min})  ${nom}`);
}
console.log(ko === 0 ? "\nToutes les paires passent." : `\n${ko} paire(s) en échec.`);

// Chart and map inks, measured against the surface they sit on (enamel-50).
console.log("\nGraphiques et carte :");
for (const [nom, a, b, min] of [
  ["série sur enamel-50", "#0a5075", "#fbfcfd", 3],
  ["texte d'axe sur enamel-50", "#5a707e", "#fbfcfd", 4.5],
  ["libellé de carte sur enamel-50", "#5a707e", "#fbfcfd", 4.5],
  ["grille sur enamel-50", "#d2dde5", "#fbfcfd", 1.2],
  ["point vous-êtes-ici sur enamel-50", "#0d1c26", "#fbfcfd", 3],
  ["pastille partenaire sur enamel-50", "#0092d8", "#fbfcfd", 3],
]) {
  const r = ratio(a, b);
  console.log(`${r >= min ? "ok " : "KO "} ${r.toFixed(2).padStart(5)} (min ${min})  ${nom}`);
}
