import { existsSync } from "node:fs";
import { join } from "node:path";
import Image from "next/image";
import { RodMark } from "@/components/rod-mark";

/**
 * The photograph on the landing field.
 *
 * The client asked for a real photograph of a practitioner so the entrance
 * feels alive rather than typographic. The file is not in the repository yet —
 * see public/accueil/README.md for what to drop in and under which licence — so
 * this component checks for it at render time and falls back to a panel that
 * says plainly that a photograph belongs here.
 *
 * That fallback is deliberate. An empty <img> would render as a broken icon, and
 * an illustration standing in for a photograph would let a reviewer believe the
 * photograph had been chosen when it had not. It reads as an unfilled slot,
 * because that is exactly what it is.
 *
 * The image is decorative: the alt text is empty and it carries no meaning the
 * headline beside it does not already give. It is never a partner's portrait —
 * PRODUCT.md forbids putting a real person's face on a fictional practice.
 */
const FILE = "/accueil/praticien.jpg";

export function HeroPortrait() {
  const present = existsSync(join(process.cwd(), "public", FILE));

  return (
    <div className="relative h-full w-full overflow-hidden bg-azur-800">
      {present ? (
        <Image
          src={FILE}
          alt=""
          fill
          priority
          sizes="(min-width: 1024px) 42vw, 100vw"
          className="object-cover object-center"
        />
      ) : (
        <div className="flex h-full w-full flex-col items-center justify-center gap-3 px-6 py-10 text-center">
          <RodMark className="h-10 w-10 text-azur-600" />
          <p className="font-display text-[11px] font-bold tracking-[0.14em] text-azur-200 uppercase">
            Photographie à placer
          </p>
          <p className="max-w-[28ch] text-sm leading-relaxed text-azur-300">
            Déposez le fichier dans{" "}
            <span className="font-display">public/accueil/praticien.jpg</span>.
            Cet emplacement disparaît dès qu&apos;il est présent.
          </p>
        </div>
      )}
    </div>
  );
}
