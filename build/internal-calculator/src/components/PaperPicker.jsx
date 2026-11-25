import React, { useState } from 'react'

export default function PaperPicker({ papers, selected, onSelect, label }) {
  const [hovered, setHovered] = useState(null)

  return (
    <div>
      {/* Label */}
      <div style={{
        fontSize: 10, fontWeight: 700, textTransform: 'uppercase',
        letterSpacing: '0.12em', color: '#555555', marginBottom: 10,
      }}>
        {label}
      </div>

      {/* Swatch grid */}
      <div style={{
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fill, minmax(52px, 1fr))',
        gap: 8,
      }}>
        {papers.map(paper => {
          const isSelected = paper.id === selected
          const isHov      = paper.id === hovered

          return (
            <div key={paper.id} style={{ position: 'relative' }}
              onMouseEnter={() => setHovered(paper.id)}
              onMouseLeave={() => setHovered(null)}
            >
              <button
                onClick={() => onSelect(paper.id)}
                style={{
                  width: '100%',
                  aspectRatio: '1 / 1',
                  borderRadius: 10,
                  border: isSelected
                    ? '3px solid #4200FF'
                    : isHov
                      ? '2px solid rgba(66,0,255,0.45)'
                      : '2px solid transparent',
                  background: paper.hex || '#ccc',
                  cursor: 'pointer',
                  padding: 0,
                  outline: 'none',
                  transition: 'transform 0.12s, border 0.12s, box-shadow 0.12s',
                  transform: isSelected ? 'scale(1.1)' : isHov ? 'scale(1.05)' : 'scale(1)',
                  boxShadow: isSelected
                    ? '0 0 0 2px #fff, 0 4px 14px rgba(66,0,255,0.35)'
                    : isHov
                      ? '0 4px 10px rgba(0,0,0,0.22)'
                      : '0 2px 5px rgba(0,0,0,0.14)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  position: 'relative',
                }}
              >
                {isSelected && (
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                    style={{ filter: 'drop-shadow(0 1px 2px rgba(0,0,0,0.3))' }}
                  >
                    <circle cx="9" cy="9" r="8" fill="rgba(255,255,255,0.88)" />
                    <path d="M5 9l3 3 5-5" stroke="#4200FF" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" />
                  </svg>
                )}
              </button>

              {/* Tooltip */}
              {isHov && (
                <div style={{
                  position: 'absolute',
                  bottom: 'calc(100% + 8px)',
                  left: '50%',
                  transform: 'translateX(-50%)',
                  background: '#000',
                  color: '#fff',
                  fontSize: 11,
                  fontWeight: 600,
                  padding: '5px 10px',
                  borderRadius: 6,
                  whiteSpace: 'nowrap',
                  pointerEvents: 'none',
                  zIndex: 999,
                  boxShadow: '0 4px 12px rgba(0,0,0,0.25)',
                }}>
                  {paper.name}
                  <div style={{
                    position: 'absolute', top: '100%', left: '50%',
                    transform: 'translateX(-50%)',
                    borderLeft: '5px solid transparent',
                    borderRight: '5px solid transparent',
                    borderTop: '5px solid #000',
                  }} />
                </div>
              )}
            </div>
          )
        })}
      </div>

      {/* Selected paper info strip */}
      {(() => {
        const p = papers.find(x => x.id === selected)
        if (!p) return null
        return (
          <div style={{
            marginTop: 10,
            display: 'flex', alignItems: 'center', gap: 10,
            padding: '8px 12px',
            background: '#F7F3FF',
            borderRadius: 8,
            border: '1.5px solid #D8CFC9',
          }}>
            <div style={{ width: 20, height: 20, borderRadius: 4, background: p.hex || '#ccc', border: '1px solid rgba(0,0,0,0.1)', flexShrink: 0 }} />
            <div>
              <div style={{ fontSize: 12, fontWeight: 700, color: '#000', lineHeight: 1.3 }}>{p.name}</div>
              <div style={{ fontSize: 11, color: '#555555' }}>{p.brand} · {p.gsm} gsm</div>
            </div>
          </div>
        )
      })()}
    </div>
  )
}
