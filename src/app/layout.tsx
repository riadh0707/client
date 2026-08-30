import type { Metadata } from "next";
import { Archivo, Atkinson_Hyperlegible } from "next/font/google";
import "./globals.css";

const archivo = Archivo({
  variable: "--font-archivo",
  subsets: ["latin"],
  display: "swap",
});

const atkinson = Atkinson_Hyperlegible({
  variable: "--font-atkinson",
  subsets: ["latin"],
  weight: ["400", "700"],
  display: "swap",
});

export const metadata: Metadata = {
  title: {
    default: "DOCTORY — Trouvez un professionnel de santé en Algérie",
    template: "%s · DOCTORY",
  },
  description:
    "Médecins, dentistes, pharmacies, laboratoires et centres d'imagerie dans les 58 wilayas. Cherchez par wilaya, commune ou autour de vous, et prenez rendez-vous.",
};

/**
 * Impeccable direction contract. Emitted into the production markup so the
 * finish review can audit the build against the direction it committed to.
 * React cannot render a bare comment node, so it rides inside a hidden element.
 */
const DIRECTION_CONTRACT = `<!--
DOCTORY — DIRECTION CONTRACT

THESIS: The pharmacy cross is not decoration, it is Algeria's existing wayfinding
system for care; DOCTORY is its screen translation. Refuses the category default
of white ground, trust-blue accent and rounded photo cards.

OWN-WORLD: Vitreous enamel plaque and lit pharmacy cross. Committed green owning
whole fields (cross-700/500) on cool enamel white, never cream. Hard 2-4px edges,
hairline rules, no soft shadow. Archivo for plaque lettering, Atkinson
Hyperlegible for text. The cross is a structural module: section marker, map pin,
grid unit. Raised by Le Carnet with ruled tabular rows and carbon-copy status
colour.

STORY: A visitor understands in one screen that this covers every kind of care in
all 58 wilayas, believes it because the geography is exact, and acts by searching
or by declaring which side of the platform they are on.

FIRST VIEWPORT: Full-bleed cross-700 field. DOCTORY in Archivo at display scale,
left. Beneath it the search instrument as a single ruled line — query, then
wilaya selector — the one bright cross-500 action at its right. Below the fold
line, two plaques: patient, professional.

FORM: "La Croix", rank 1 of my grounded list, chosen by the user over the roll's
assignment (index 3, "Le Carnet"). Seed key 2590fc2b.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
-->`;

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html
      lang="fr"
      className={`${archivo.variable} ${atkinson.variable} h-full antialiased`}
    >
      <body className="flex min-h-full flex-col">
        <div hidden dangerouslySetInnerHTML={{ __html: DIRECTION_CONTRACT }} />
        {children}
      </body>
    </html>
  );
}
