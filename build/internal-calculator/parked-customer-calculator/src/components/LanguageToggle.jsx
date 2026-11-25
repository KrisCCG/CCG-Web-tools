import React from 'react'
import { useTranslation } from '../utils/i18n.jsx'

const accent = '#4200FF'

export default function LanguageToggle() {
  const { lang, setLang } = useTranslation()

  return (
    <div style={{ display: 'flex', gap: 4 }}>
      {['en', 'fr', 'nl'].map(l => (
        <button
          key={l}
          onClick={() => setLang(l)}
          style={{
            padding: '4px 10px',
            borderRadius: 6,
            border: 'none',
            cursor: 'pointer',
            fontSize: 11,
            fontWeight: 700,
            textTransform: 'uppercase',
            background: lang === l ? accent : 'rgba(0,0,0,0.05)',
            color: lang === l ? '#fff' : '#999',
            transition: 'all 0.15s',
          }}
        >
          {l}
        </button>
      ))}
    </div>
  )
}
