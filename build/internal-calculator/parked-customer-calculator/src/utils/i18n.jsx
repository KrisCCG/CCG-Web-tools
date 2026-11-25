import React, { createContext, useContext, useState } from 'react'
import T from './translations.js'

const SUPPORTED = ['en', 'fr', 'nl']
const STORAGE_KEY = 'ccg-lang'

const getInitialLang = () => {
  if (typeof window === 'undefined') return 'en'
  const stored = localStorage.getItem(STORAGE_KEY)
  if (stored && SUPPORTED.includes(stored)) return stored
  // Auto-detect from browser
  const nav = (navigator.language || '').slice(0, 2).toLowerCase()
  if (nav === 'fr') return 'fr'
  if (nav === 'nl') return 'nl'
  return 'en'
}

const LanguageContext = createContext()

export function LanguageProvider({ children }) {
  const [lang, setLangState] = useState(getInitialLang)

  const setLang = l => {
    if (SUPPORTED.includes(l)) {
      setLangState(l)
      localStorage.setItem(STORAGE_KEY, l)
    }
  }

  const t = (key, vars) => {
    const entry = T[key]
    if (!entry) return key
    let str = entry[lang] || entry.en || key
    if (vars) {
      Object.entries(vars).forEach(([k, v]) => {
        str = str.replace(`{${k}}`, v)
      })
    }
    return str
  }

  const tArray = key => {
    const entry = T[key]
    if (!entry) return []
    return entry[lang] || entry.en || []
  }

  return (
    <LanguageContext.Provider value={{ lang, setLang, t, tArray }}>
      {children}
    </LanguageContext.Provider>
  )
}

export function useTranslation() {
  return useContext(LanguageContext)
}
