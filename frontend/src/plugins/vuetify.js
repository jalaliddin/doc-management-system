import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'

export default createVuetify({
  components,
  directives,
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: { mdi },
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#0096C7',
          secondary: '#00B4D8',
          accent: '#0077B6',
          error: '#EF4444',
          success: '#10B981',
          surface: '#FFFFFF',
          background: '#F8FAFC',
        },
      },
    },
  },
})
