---
name: mygarden-design
description: >
  Activate when working on myGarden UI components, design tokens, or the
  design system reference page. Covers semantic tokens, component patterns,
  font usage, and the design-system rules.
triggers:
  - resources/js/components/mygarden/
  - resources/css/app.css
  - resources/js/pages/dev/design-system.tsx
  - .claude/rules/design-system.md
---

# myGarden Design Skill

## Wann aktivieren

Bei Arbeit an:
- `resources/js/components/mygarden/` (PageHeader, EmptyState, FormSection, StatusBadge, VisibilityBadge, ImagePlaceholder)
- `resources/css/app.css` (Design-Tokens)
- `resources/js/pages/dev/design-system.tsx` (Referenzseite)
- Neuen React-Komponenten, die Design-System-Konformität benötigen

## Live-Referenz

`/dev/design-system` (nur lokal erreichbar) zeigt alle Tokens und Komponenten interaktiv.

## Semantische Token-Referenz

| Token | Bedeutung | Primitivquelle |
|---|---|---|
| `background` | Seitenhintergrund | sand-50 |
| `foreground` | Primärer Text | forest-900 |
| `card` | Karten-Oberfläche | sand-50 leicht erhöht |
| `primary` | Primäraktion | forest-700 (light), forest-400 (dark) |
| `secondary` | Sekundärer Hintergrund | sand-200 |
| `muted` | Gedämpfter Hintergrund | sand-100 |
| `muted-foreground` | Hilfstexte | neutral-700 |
| `accent` | Terrakotta-Akzent (sparsam!) | terracotta-500 |
| `destructive` | Fehler/Löschen | red-600 |
| `success` | Erfolgsstatus | grün oklch(0.55 0.10 150) |
| `warning` | Warnung (Amber, Hue 55, ≠ Terrakotta) | oklch(0.68 0.13 55) |
| `border` | Rahmen | warm sand-tint |
| `ring` | Fokus-Ring | forest-500 |
| `sidebar` | Sidebar-Hintergrund | sand-100 |

## Font-Verwendung

```tsx
// Produkt-UI (Standard, kein extra class nötig):
<p>Text im Sans-Stil</p>

// Messwerte und IDs:
<code className="font-mono">pH: 6.8</code>

// NUR für Marketing-Hero (nie in App-UI):
<h1 className="font-serif text-4xl">Headline</h1>
```

## myGarden-Komponenten

### PageHeader

```tsx
import { PageHeader } from '@/components/mygarden/page-header';

<PageHeader
  title="Meine Gärten"
  description="Verwalte deine Gärten."
  actions={<Button>Neuer Garten</Button>}
/>
```

### EmptyState

```tsx
import { EmptyState } from '@/components/mygarden/empty-state';
import { Sprout } from 'lucide-react';

<EmptyState
  icon={Sprout}
  title="Noch kein Garten angelegt"
  description="Erstelle deinen ersten Garten."
  action={<Button size="sm">Garten anlegen</Button>}
/>
```

### FormSection

```tsx
import { FormSection } from '@/components/mygarden/form-section';

<FormSection title="Allgemeine Infos" description="Grunddaten zum Garten.">
  <Input name="name" placeholder="Gartenname" />
</FormSection>
```

### StatusBadge

```tsx
import { StatusBadge } from '@/components/mygarden/status-badge';

// variant ist explizit, keine magic string-zu-Farbe Logik:
<StatusBadge variant="default" label="Aktiv" />
<StatusBadge variant="secondary" label="Inaktiv" />
<StatusBadge variant="destructive" label="Archiviert" />
```

### VisibilityBadge

```tsx
import { VisibilityBadge } from '@/components/mygarden/visibility-badge';

// Gültige Werte: 'private' | 'connections' | 'unlisted' | 'public'
<VisibilityBadge visibility="private" />
<VisibilityBadge visibility="public" />
```

Der Typ `GardenVisibility` ist lokal in `visibility-badge.tsx` definiert
und wird durch einen Backend-Enum ersetzt, sobald das Garten-Modul existiert.

### ImagePlaceholder

```tsx
import { ImagePlaceholder } from '@/components/mygarden/image-placeholder';

<ImagePlaceholder aspectRatio="4/3" label="Foto hinzufügen" />
```

## Barrel-Import

```tsx
import { PageHeader, EmptyState, FormSection } from '@/components/mygarden';
```

## Verbotene Muster

```tsx
// ❌ Direkte Farbwerte:
<div style={{ color: '#3a5a4a' }}>
<div className="text-[#3a5a4a]">
<div className="bg-neutral-100">

// ✅ Semantische Tokens:
<div className="text-foreground">
<div className="bg-muted">
<div className="text-muted-foreground">

// ❌ Lucide CDN:
import { Sprout } from 'https://cdn.skypack.dev/lucide-react';

// ✅ npm-Paket:
import { Sprout } from 'lucide-react';
```
