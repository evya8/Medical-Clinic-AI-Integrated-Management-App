/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL: string
  readonly VITE_APP_NAME: string
  readonly VITE_APP_VERSION: string
  readonly VITE_ENABLE_AI_FEATURES: string
  readonly VITE_ENABLE_DEBUG_MODE: string
  readonly VITE_LOG_LEVEL: string
  readonly VITE_SHOW_DEMO_CREDENTIALS: string
  readonly VITE_MOCK_API_DELAY: string
  readonly VITE_ENABLE_PERFORMANCE_MONITORING: string
  readonly VITE_SENTRY_DSN?: string
  readonly VITE_GOOGLE_ANALYTICS_ID?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
