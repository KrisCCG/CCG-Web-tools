import React from 'react'
import ReactDOM from 'react-dom/client'
import { LanguageProvider } from './utils/i18n.jsx'
import CustomerCalculator from './calculators/CustomerCalculator.jsx'

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <LanguageProvider>
      <CustomerCalculator />
    </LanguageProvider>
  </React.StrictMode>
)
