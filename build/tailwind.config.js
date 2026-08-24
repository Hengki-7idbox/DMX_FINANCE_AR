/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './preview/**/*.html',
  ],
  theme: {
    extend: {
      colors: {
        /* ── Semantic Colors (mapped from CSS vars) ── */
        background:   'var(--color-background)',
        foreground:   'var(--color-foreground)',
        card:         { DEFAULT: 'var(--color-background-card)', foreground: 'var(--color-foreground)' },
        muted:        { DEFAULT: 'var(--color-muted)', foreground: 'var(--color-muted-foreground)' },
        accent:       { DEFAULT: 'var(--color-accent)', foreground: 'var(--color-accent-foreground)' },
        border:       'var(--color-border)',

        /* ── Primary (Blue) ── */
        primary: {
          DEFAULT: 'var(--color-primary)',
          hover:   'var(--color-primary-hover)',
          active:  'var(--color-primary-active)',
          foreground: 'var(--color-primary-foreground)',
          subtle:  'var(--color-primary-subtle)',
          muted:   'var(--color-primary-muted)',
          50:  'var(--color-blue-50)',
          100: 'var(--color-blue-100)',
          200: 'var(--color-blue-200)',
          300: 'var(--color-blue-300)',
          400: 'var(--color-blue-400)',
          500: 'var(--color-blue-500)',
          600: 'var(--color-blue-600)',
          700: 'var(--color-blue-700)',
          800: 'var(--color-blue-800)',
          900: 'var(--color-blue-900)',
        },

        /* ── Success (Green) ── */
        success: {
          DEFAULT: 'var(--color-success)',
          hover:   'var(--color-success-hover)',
          foreground: 'var(--color-success-foreground)',
          subtle:  'var(--color-success-subtle)',
          muted:   'var(--color-success-muted)',
        },

        /* ── Warning (Yellow) ── */
        warning: {
          DEFAULT: 'var(--color-warning)',
          hover:   'var(--color-warning-hover)',
          foreground: 'var(--color-warning-foreground)',
          subtle:  'var(--color-warning-subtle)',
          muted:   'var(--color-warning-muted)',
        },

        /* ── Danger (Red) ── */
        danger: {
          DEFAULT: 'var(--color-danger)',
          hover:   'var(--color-danger-hover)',
          foreground: 'var(--color-danger-foreground)',
          subtle:  'var(--color-danger-subtle)',
          muted:   'var(--color-danger-muted)',
        },

        /* ── Attention (Orange) ── */
        attention: {
          DEFAULT: 'var(--color-attention)',
          hover:   'var(--color-attention-hover)',
          foreground: 'var(--color-attention-foreground)',
          subtle:  'var(--color-attention-subtle)',
        },

        /* ── Sidebar ── */
        sidebar: {
          bg:     'var(--color-sidebar-bg)',
          border: 'var(--color-sidebar-border)',
          hover:  'var(--color-sidebar-hover)',
          active: 'var(--color-sidebar-active)',
          text:   'var(--color-sidebar-text)',
          'text-active': 'var(--color-sidebar-text-active)',
        },

        /* ── Topbar ── */
        topbar: {
          bg:     'var(--color-topbar-bg)',
          border: 'var(--color-topbar-border)',
        },

        /* ── Gray scale (for direct use) ── */
        gray: {
          50:  'var(--color-gray-50)',
          100: 'var(--color-gray-100)',
          200: 'var(--color-gray-200)',
          300: 'var(--color-gray-300)',
          400: 'var(--color-gray-400)',
          500: 'var(--color-gray-500)',
          600: 'var(--color-gray-600)',
          700: 'var(--color-gray-700)',
          800: 'var(--color-gray-800)',
          900: 'var(--color-gray-900)',
          950: 'var(--color-gray-950)',
        },
      },

      fontFamily: {
        sans: ['Open Sans', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
        heading: ['Poppins', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
        mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'monospace'],
      },

      fontSize: {
        xs:   'var(--font-size-xs)',
        sm:   'var(--font-size-sm)',
        base: 'var(--font-size-base)',
        lg:   'var(--font-size-lg)',
        xl:   'var(--font-size-xl)',
        '2xl': 'var(--font-size-2xl)',
        '3xl': 'var(--font-size-3xl)',
      },

      borderRadius: {
        sm:   'var(--radius-sm)',
        md:   'var(--radius-md)',
        lg:   'var(--radius-lg)',
        xl:   'var(--radius-xl)',
        '2xl': 'var(--radius-2xl)',
      },

      boxShadow: {
        sm:  'var(--shadow-sm)',
        md:  'var(--shadow-md)',
        lg:  'var(--shadow-lg)',
        xl:  'var(--shadow-xl)',
      },

      spacing: {
        '0.5': 'var(--space-1)',
        '1':   'var(--space-2)',
        '1.5': 'var(--space-3)',
        '2':   'var(--space-4)',
        '2.5': 'var(--space-5)',
        '3':   'var(--space-6)',
        '4':   'var(--space-8)',
        '5':   'var(--space-10)',
        '6':   'var(--space-12)',
        '8':   'var(--space-16)',
      },

      transitionDuration: {
        fast: '150ms',
        base: '200ms',
        slow: '300ms',
      },

      zIndex: {
        dropdown: '50',
        sticky:   '100',
        overlay:  '200',
        modal:    '300',
        toast:    '400',
      },
    },
  },
  plugins: [],
}
