# myGarden Design System – Verbindliche Regeln

Diese Regeln gelten für alle React-Komponenten und Seiten unter `resources/js/`.

## Farben

- Keine direkten Hex-, RGB-, HSL- oder OKLch-Werte in TSX-Dateien.
- Keine `neutral-NNN`-Tailwind-Klassen (z.B. `text-neutral-500`, `bg-neutral-100`), wenn ein semantisches Token existiert.
- Semantische Token-Klassen verwenden: `bg-primary`, `text-muted-foreground`, `border-border`, `ring-ring` usw.
- `bg-accent` ist Terrakotta – sparsam und bewusst einsetzen, nicht als universeller Hover-Hintergrund.
- Ghost- und Outline-Buttons verwenden `hover:bg-muted hover:text-foreground` als neutralen Hover.
- Neue Farbwerte müssen als Tokens in `resources/css/app.css` definiert werden, nicht inline.

## Fonts

- `font-sans` (Work Sans): gesamtes Produkt-UI – Standard.
- `font-serif` (Source Serif 4): ausschließlich Marketing- und Hero-Texte – **niemals in der App-UI**.
  - Ausnahme: `resources/js/pages/dev/design-system.tsx` darf `font-serif` als Typografie-Specimen verwenden.
- `font-mono` (JetBrains Mono): Messwerte, Identifikatoren, Code-Blöcke.

## Icons

- Ausschließlich `lucide-react` npm-Paket verwenden.
- Kein CDN-Import (`lucide-static`, esm.sh, unpkg etc.).

## Dark Mode

- Jede Komponente, die Licht-Stile setzt, muss auch Dark-Mode-Varianten berücksichtigen.
- Semantische Tokens passen sich automatisch an – direkte Farbwerte nicht.

## Sprache

- Du-Form in allen UI-Texten und Beschriftungen.
- Ruhig, freundlich, kompetent – nicht kindlich.
- Keine Emoji als reguläre UI-Elemente.

## Komponenten-Struktur

- Vor dem Erstellen neuer Komponenten: prüfen ob eine existierende shadcn-Komponente ausreicht.
- Allgemeine shadcn-Primitiven: `resources/js/components/ui/`
- Wiederverwendbare myGarden-Muster: `resources/js/components/mygarden/`
- Dateinamen: kebab-case (z.B. `page-header.tsx`), Exports: PascalCase.

## Interaktionszustände

Jede interaktive Komponente muss alle relevanten Zustände abdecken:
- Hover
- Focus (sichtbarer Fokus-Ring über `focus-visible:ring-*`)
- Disabled (`disabled:opacity-50 disabled:cursor-not-allowed`)
- Error (`aria-invalid`, `border-destructive`, `ring-destructive`)
- Loading (Spinner oder Skeleton)

## Responsive Design

- Mobile-first: `sm:`, `md:`, `lg:` Breakpoints nutzen.
- Kein festes `px`-Breiten-Hardcoding ohne Begründung.

## Neue Tokens

- Neue Design-Tokens müssen in `resources/css/app.css` definiert werden.
- Light und Dark Mode immer vollständig.
- Begründung als Kommentar im CSS.
- Kein Token ohne WCAG-AA-Kontrast-Nachweis.

## Quality Gate

Vor dem Commit: `npm run check:colors` muss 0 Violations zeigen.
