---
name: Smart Domestic IoT
colors:
  surface: '#f7f9ff'
  surface-dim: '#d1dbe8'
  surface-bright: '#f7f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#edf4ff'
  surface-container: '#e5effc'
  surface-container-high: '#dfe9f6'
  surface-container-highest: '#d9e3f1'
  on-surface: '#131c26'
  on-surface-variant: '#3c4a47'
  inverse-surface: '#28313b'
  inverse-on-surface: '#e8f2ff'
  outline: '#6b7a77'
  outline-variant: '#bacac6'
  surface-tint: '#006a61'
  primary: '#006a61'
  on-primary: '#ffffff'
  primary-container: '#00c4b4'
  on-primary-container: '#004b44'
  inverse-primary: '#3edccc'
  secondary: '#005daa'
  on-secondary: '#ffffff'
  secondary-container: '#0075d5'
  on-secondary-container: '#fefcff'
  tertiary: '#006c46'
  on-tertiary: '#ffffff'
  tertiary-container: '#2dc687'
  on-tertiary-container: '#004c30'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#63f9e8'
  primary-fixed-dim: '#3edccc'
  on-primary-fixed: '#00201d'
  on-primary-fixed-variant: '#005049'
  secondary-fixed: '#d4e3ff'
  secondary-fixed-dim: '#a5c8ff'
  on-secondary-fixed: '#001c3a'
  on-secondary-fixed-variant: '#004785'
  tertiary-fixed: '#6ffcb8'
  tertiary-fixed-dim: '#4edf9e'
  on-tertiary-fixed: '#002112'
  on-tertiary-fixed-variant: '#005234'
  background: '#f7f9ff'
  on-background: '#131c26'
  surface-variant: '#d9e3f1'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 26px
    fontWeight: '600'
    lineHeight: 34px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 30px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 26px
  title-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  title-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 18px
  label-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.01em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 10px
    fontWeight: '600'
    lineHeight: 14px
    letterSpacing: 0.02em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1rem
  gutter-desktop: 1.5rem
  margin: 1rem
  margin-tablet: 1.5rem
  margin-desktop: 2.5rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.25rem
  space-xl: 1.5rem
---

## Brand & Style

This design system channels the restrained, functional, and humanistic visual language of modern smart home hardware and control applications. Built for interconnected household appliances—specifically smart laundry monitoring, sorting, and care orchestration—the interface balances technical clarity with domestic serenity. 

The aesthetic is Minimalist and Tactile-Soft: an evolution of contemporary hardware-companion interfaces characterized by airy white planes, calm ambient lighting, organic rounded containers, and hyper-legible status indicators. The experience eliminates cognitive overload by foregrounding contextual state changes (weight capacity, fabric type detection, wash cycle suggestions, dampness levels) through micro-interactions and soft color signaling. The emotional tone is calm, hygienic, intuitive, and reassuring.

## Colors

The palette establishes high clarity through clean semantic partitioning, maintaining visual calm while immediately communicating appliance status:

- **Primary Accent (`#00C4B4` - Mijia Smart Mint/Teal):** The core active state. Represents active tracking, connected states, active ventilation/deodorization, and confirmed smart actions.
- **Secondary Accent (`#1890FF` - Hydro Sky Blue):** Represents moisture management, water-related sensor data, washing machine handoffs, and interactive utilities.
- **Tertiary Accent (`#00B578` - Eco Green):** Indicates optimal loads, fresh/sanitized laundry state, and energy-efficient cycle suggestions.
- **Warning & Attention (`#FF6700` - Signature Warm Coral):** Reserved strictly for capacity thresholds (e.g., basket 90%+ full), bin ventilation alerts, and prompt-oriented status.
- **System Hazard & Danger (`#F53F3F` - Soft Crimson):** Critical alerts such as prolonged dampness detection, mold risk alert, battery depletion, or connectivity lost.
- **Canvas & Surface Palette:**
  - Base Background: Warm off-white canvas (`#F7F8FA`).
  - Surface Containers: Pure crisp white (`#FFFFFF`) to produce seamless layering over the base.
  - Subdued Field Fill: Gentle gray fill (`#F2F3F5`) for inner wells, segmented controls, and inactive toggle tracks.
- **Text & Foreground:**
  - Heading & Primary Text: High-contrast soft charcoal (`#1D2129`).
  - Secondary & Metadata Text: Balanced neutral slate (`#86909C`).
  - Tertiary / Disabled / Dividers: Low-contrast line and placeholder tint (`#C9CDD4`).

## Typography

The design system standardizes on **Plus Jakarta Sans** across all hierarchies. Its geometric construction, generous counters, and humanized rounded terminals deliver high clarity for numerical sensor readouts, hardware status labels, and domestic care guides.

- Large metric indicators (e.g., basket fill percentage, weight in kg, days accumulated) utilize tabular figure styling via open-type feature settings to prevent metric jitter during live polling.
- Headlines prioritize semi-bold and bold weights to ground the soft card surfaces.
- Body copy and sub-labels avoid harsh pure blacks, employing `#1D2129` for primary reading and `#86909C` for secondary status text to maintain an airy visual rhythm.

## Layout & Spacing

The layout is built on a responsive fluid grid system anchored by a baseline unit of 4px / 8px. It ensures touch targets on mobile IoT companion devices maintain ergonomics while comfortably scaling into expanded dashboard views on tablets and wall displays.

- **Mobile Viewports (<600px):** Single-column layout with 16px horizontal page margins and 16px component gaps. Sensor hero modules span full width.
- **Tablet / Smart Display Viewports (600px–1024px):** 6-column fluid grid with 24px page margins and 20px gutters. Device telemetry splits into dual-pane primary cards.
- **Desktop / Wide Display Viewports (>1024px):** 12-column fixed-max grid (capped at 1280px container width) with 40px margins and 24px gutters. Provides modular multi-appliance status monitoring.
- **Internal Spacing Rules:** Cards maintain a generous internal padding (`space-xl` / 20–24px) to retain breathing room around high-density appliance metrics.

## Elevation & Depth

Visual hierarchy relies on high-diffusion ambient shadows and soft tonal planar separation rather than structural outlines or harsh drop shadows. 

- **Surface Contrast:** Pure white `#FFFFFF` component cards rest upon the neutral off-white `#F7F8FA` background, creating instant edge definition without drawing borders.
- **Ambient Shadow (Resting Card Elevation):** `box-shadow: 0px 8px 24px rgba(29, 33, 41, 0.04), 0px 2px 6px rgba(29, 33, 41, 0.02)`. This creates a floating, weightless appearance reminiscent of premium consumer electronics packaging.
- **Interactive & Raised State:** On tap, drag, or active appliance state: `box-shadow: 0px 12px 32px rgba(0, 196, 180, 0.12), 0px 4px 12px rgba(29, 33, 41, 0.03)`. A faint, colored ambient glow matching the active primary mint or alert hue reinforces hardware connectivity.
- **Wells and Recessed Tracks:** Sliders, meter containers, and inactive toggle beds utilize negative tonal depth (`#F2F3F5` fill with no shadow) to simulate subtle molded injection-plastic recesses.

## Shapes

The shape vocabulary mirrors physical Mijia/smart ecosystem hardware: smooth injection-molded forms with generous, continuous-curvature radii.

- **Cards and Containers:** Feature substantial corner radii (20px to 24px / `rounded-xl`), creating organic silhouettes that soften technical data.
- **Interactive Badges and Action Chips:** Feature full pill rounding (`rounded-full` / 9999px) for friendly tactile ergonomics.
- **Toggles, Sliders, and Metric Bars:** Fully rounded extremities prevent sharp visual terminations across continuous measurement gauges.

## Components

### 1. Cards
- **Base Style:** Seamless pure white (`#FFFFFF`) surface with zero border stroke, 20px to 24px border radius, resting on the ambient shadow formula.
- **Hero Telemetry Card:** Contains the smart basket's real-time volumetric state, moisture gauge, and weight readouts. Employs large tabular display numbers paired with small secondary unit markers (`kg`, `%`, `°C`).
- **Interactive Sub-Card:** Tap-accessible cards within the main grid that reveal wash presets, fabric separation recommendations, and odor-neutralization settings.

### 2. Buttons & Actions
- **Primary Action Button:** Full pill shape (`rounded-full`), height 48px or 52px, filled with Smart Mint (`#00C4B4`) with white typography. Hover/active state deepens slightly to `#00B578`.
- **Secondary Action Button:** Surface fill `#F2F3F5`, charcoal text (`#1D2129`), no border.
- **Floating Contextual Button:** Circular (56px × 56px) or pill button floating with strong diffused shadow for rapid appliance triggers (e.g., "Start Deodorize Cycle").

### 3. Pill Badges & Status Chips
- Height 24px–28px with full pill curvature and horizontal padding of 12px.
- **Air Quality / Sanitization Pill:** Mint-tinted background (`rgba(0, 196, 180, 0.1)`) with `#00A396` text.
- **Moisture Alert Pill:** Sky blue background (`rgba(24, 144, 255, 0.1)`) with `#1890FF` text.
- **Full Capacity Warning Pill:** Coral background (`rgba(255, 103, 0, 0.1)`) with `#FF6700` text.

### 4. Interactive Toggles & Segmented Controls
- **IoT Device Toggle:** 50px × 30px track with a 26px pure white circular knob. When disabled: track is `#E5E6EB`. When enabled: track switches to `#00C4B4` with a smooth 200ms cubic-bezier transition.
- **Fabric/Load Segmented Selector:** Contained within a rounded-full `#F2F3F5` track; active item shifts via an animated pure white floating pill pill-tab with soft drop shadow.

### 5. Inputs & Form Steppers
- Clean light background (`#F7F8FA`) with no border, becoming tinted with a 1px soft `#00C4B4` focus ring when active.
- Weight and cycle adjustment buttons use rounded-full circular micro-buttons (`+` / `–`) with haptic-styled state transitions.

### 6. Specialized Smart Basket Indicators
- **Radial Capacity Ring:** Smooth circular SVG progress gauge utilizing a 10px stroke with rounded caps. Background stroke `#F2F3F5`, dynamic fill stroke transitioning from `#00C4B4` (normal) to `#FF6700` (over 85% capacity).
- **Ventilation Waveform:** Animated subtle bar equalizer indicating active fan or ozone sterilization in progress.