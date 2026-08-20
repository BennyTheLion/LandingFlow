import { Suspense, lazy, useEffect, useRef, useState } from 'react'
import HeroErrorBoundary from './HeroErrorBoundary.jsx'

// Lazy-loaded so the (fairly heavy) three.js/R3F bundle is only fetched
// when we're actually going to mount the canvas.
const HeroScene = lazy(() => import('./HeroScene.jsx'))

function useMediaQuery(query) {
  const [matches, setMatches] = useState(
    () => typeof window !== 'undefined' && window.matchMedia(query).matches,
  )

  useEffect(() => {
    const mql = window.matchMedia(query)
    const handler = (e) => setMatches(e.matches)
    mql.addEventListener('change', handler)
    return () => mql.removeEventListener('change', handler)
  }, [query])

  return matches
}

// Static fallback used on mobile and when prefers-reduced-motion is set -
// no WebGL context is ever created in either case.
function StaticHeroBackground() {
  return (
    <div
      className="absolute inset-0"
      style={{
        background:
          'radial-gradient(60% 50% at 50% 35%, rgba(34,197,94,0.16), transparent 70%), linear-gradient(180deg, #0b0f14 0%, #0d1420 100%)',
      }}
    />
  )
}

export default function Hero() {
  const isMobile = useMediaQuery('(max-width: 767px)')
  const prefersReducedMotion = useMediaQuery('(prefers-reduced-motion: reduce)')
  const useCanvas = !isMobile && !prefersReducedMotion
  const [canvasKey, setCanvasKey] = useState(0)
  const pendingRecovery = useRef(false)

  // Remounting immediately on context loss can loop forever if the tab is
  // backgrounded (a freshly created context just gets starved/lost again
  // right away) - defer the remount until the page is actually visible.
  const handleContextLost = () => {
    if (document.visibilityState === 'visible') {
      setCanvasKey((k) => k + 1)
    } else {
      pendingRecovery.current = true
    }
  }

  useEffect(() => {
    const onVisibilityChange = () => {
      if (document.visibilityState === 'visible' && pendingRecovery.current) {
        pendingRecovery.current = false
        setCanvasKey((k) => k + 1)
      }
    }
    document.addEventListener('visibilitychange', onVisibilityChange)
    return () => document.removeEventListener('visibilitychange', onVisibilityChange)
  }, [])

  return (
    <section
      dir="rtl"
      className="relative flex min-h-[85dvh] w-full flex-col items-center justify-center overflow-hidden bg-[#0b0f14] px-5 py-16 text-white sm:px-8"
    >
      {/* 3D scene / static fallback */}
      <div className="absolute inset-0">
        {useCanvas ? (
          <HeroErrorBoundary resetKey={canvasKey} fallback={<StaticHeroBackground />}>
            <Suspense fallback={<StaticHeroBackground />}>
              <HeroScene key={canvasKey} onContextLost={handleContextLost} />
            </Suspense>
          </HeroErrorBoundary>
        ) : (
          <StaticHeroBackground />
        )}
      </div>

      {/* readability gradient behind the text */}
      <div
        className="pointer-events-none absolute inset-0"
        style={{
          background:
            'radial-gradient(60% 55% at 50% 42%, rgba(11,15,20,0.55) 0%, rgba(11,15,20,0.15) 55%, rgba(11,15,20,0) 75%)',
        }}
      />

      {/* content */}
      <div className="relative z-10 flex w-full max-w-2xl flex-col items-center text-center">
        <h1 className="text-3xl font-extrabold leading-tight sm:text-5xl md:text-6xl">
          פתרונות טכנולוגיים לעסק שלך,
          <br />
          <span className="text-[#22c55e]">בליווי אישי צמוד.</span>
        </h1>

        <p className="mt-5 max-w-xl text-base leading-relaxed text-white/70 sm:text-lg">
          דפי נחיתה, אתרים, אוטומציות AI וניטור — הכל מאדם אחד שמבין
          טכנולוגיה ומכיר אותך אישית.
        </p>

        <div className="mt-8 flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:justify-center">
          <a
            href="/demo"
            className="inline-flex min-h-[52px] w-full items-center justify-center gap-2 rounded-xl bg-[#22c55e] px-7 py-3.5 text-base font-bold text-[#0b0f14] transition-transform duration-200 hover:scale-[1.03] active:scale-[0.98] sm:w-auto"
          >
            🚀 צור אתר דמו עכשיו
          </a>
          <a
            href="/audit"
            className="inline-flex min-h-[52px] w-full items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/5 px-7 py-3.5 text-base font-bold text-white backdrop-blur-sm transition-colors duration-200 hover:bg-white/10 sm:w-auto"
          >
            🔍 בדיקת אתר חינם
          </a>
        </div>

        <div className="mt-6 inline-flex items-center gap-2 rounded-full bg-[#22c55e]/10 px-4 py-2 text-sm font-medium text-[#22c55e]">
          <span className="relative flex h-2 w-2">
            <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#22c55e] opacity-60" />
            <span className="relative inline-flex h-2 w-2 rounded-full bg-[#22c55e]" />
          </span>
          💬 וואטסאפ ישיר — לא כרטיס תמיכה, קשר אישי
        </div>
      </div>

      {/* scroll indicator */}
      <div className="absolute bottom-6 left-1/2 z-10 -translate-x-1/2 opacity-60">
        <div className="h-9 w-5 rounded-full border border-white/30 p-1">
          <div className="mx-auto h-1.5 w-1.5 animate-bounce rounded-full bg-white/70" />
        </div>
      </div>
    </section>
  )
}
