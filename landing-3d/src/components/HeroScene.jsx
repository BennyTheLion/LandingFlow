import { useEffect, useMemo, useRef, useState } from 'react'
import { Canvas, useFrame } from '@react-three/fiber'
import { Line, RoundedBox, Text } from '@react-three/drei'
import { EffectComposer, Bloom } from '@react-three/postprocessing'
import gsap from 'gsap'
import * as THREE from 'three'

const ACCENT = '#22c55e'

// Layer 1 (bottom) - monitoring: a live pulse line + a "site up" dot with
// radar-style ping rings + a live uptime number, standing in for 24/7 checks.
function MonitoringLayer() {
  const dotRef = useRef(null)
  const ring1Ref = useRef(null)
  const ring2Ref = useRef(null)

  const pulsePoints = useMemo(
    () => [
      [-1.15, 0, 0],
      [-0.55, 0, 0],
      [-0.4, 0.22, 0],
      [-0.28, -0.32, 0],
      [-0.14, 0.14, 0],
      [0, 0, 0],
      [0.58, 0, 0],
    ],
    [],
  )

  useFrame((state) => {
    const t = state.clock.getElapsedTime()
    if (dotRef.current) {
      dotRef.current.scale.setScalar(1 + Math.sin(t * 2.4) * 0.25)
    }
    ;[ring1Ref, ring2Ref].forEach((ref, i) => {
      if (!ref.current) return
      const cycle = (t * 0.5 + i * 0.5) % 1
      ref.current.scale.setScalar(0.06 + cycle * 0.55)
      ref.current.material.opacity = Math.max(0, 0.5 * (1 - cycle))
    })
  })

  return (
    <group>
      <Line points={pulsePoints} color={ACCENT} lineWidth={1.4} transparent opacity={0.8} />
      <mesh ref={dotRef} position={[0.58, 0, 0.01]}>
        <circleGeometry args={[0.045, 20]} />
        <meshBasicMaterial
          color={ACCENT}
          transparent
          opacity={0.95}
          blending={THREE.AdditiveBlending}
          depthWrite={false}
        />
      </mesh>
      {[ring1Ref, ring2Ref].map((ref, i) => (
        <mesh key={i} ref={ref} position={[0.58, 0, 0.01]}>
          <ringGeometry args={[0.09, 0.11, 32]} />
          <meshBasicMaterial
            color={ACCENT}
            transparent
            opacity={0.5}
            blending={THREE.AdditiveBlending}
            depthWrite={false}
            side={THREE.DoubleSide}
          />
        </mesh>
      ))}
      <Text
        position={[0.58, -0.24, 0.01]}
        fontSize={0.11}
        color={ACCENT}
        anchorX="center"
        anchorY="middle"
        font={undefined}
      >
        99.9%
      </Text>
      <Text
        position={[-1.15, -0.24, 0.01]}
        fontSize={0.065}
        color="#ffffff"
        fillOpacity={0.5}
        anchorX="center"
        anchorY="middle"
      >
        UPTIME
      </Text>
    </group>
  )
}

// Layer 2 (middle) - site audit: a score gauge (like the real audit report's
// score ring) with the score number in the middle, plus a short checklist.
function AuditLayer() {
  const radius = 0.4

  const trackPoints = useMemo(() => {
    const pts = []
    const segments = 48
    for (let i = 0; i <= segments; i++) {
      const a = (i / segments) * Math.PI * 2
      pts.push([Math.cos(a) * radius, Math.sin(a) * radius, 0])
    }
    return pts
  }, [])

  const arcPoints = useMemo(() => {
    const pts = []
    const start = Math.PI * 0.65
    const end = start + Math.PI * 1.65 // ~82 out of 100 filled
    const segments = 48
    for (let i = 0; i <= segments; i++) {
      const a = start + (end - start) * (i / segments)
      pts.push([Math.cos(a) * radius, Math.sin(a) * radius, 0])
    }
    return pts
  }, [])

  const checks = [0.18, 0, -0.18]

  return (
    <group>
      <group position={[-0.55, 0.05, 0]}>
        <Line points={trackPoints} color="#ffffff" lineWidth={0.8} transparent opacity={0.12} />
        <Line points={arcPoints} color={ACCENT} lineWidth={1.7} transparent opacity={0.85} />
        <Text position={[0, 0.03, 0.01]} fontSize={0.24} color="#ffffff" anchorX="center" anchorY="middle">
          82
        </Text>
        <Text
          position={[0, -0.16, 0.01]}
          fontSize={0.052}
          color="#ffffff"
          fillOpacity={0.5}
          anchorX="center"
          anchorY="middle"
        >
          SCORE
        </Text>
      </group>
      <group position={[0.4, 0.05, 0]}>
        {checks.map((y, i) => (
          <group key={i} position={[0, y, 0]}>
            <Line
              points={[
                [-0.16, 0, 0],
                [-0.07, -0.06, 0],
                [0.06, 0.09, 0],
              ]}
              color={ACCENT}
              lineWidth={1.3}
              transparent
              opacity={0.85}
            />
            <mesh position={[0.34, 0.01, 0]}>
              <planeGeometry args={[0.46, 0.032]} />
              <meshBasicMaterial color="#ffffff" transparent opacity={0.18} depthWrite={false} />
            </mesh>
          </group>
        ))}
      </group>
    </group>
  )
}

// Animated "typing" code lines - each line grows from its start edge on a
// staggered loop, then blanks and retypes, suggesting live running code.
function TypingCode() {
  const groupRefs = useRef([])
  const lineDefs = useMemo(
    () => [
      { w: 0.55, y: 0.3, color: ACCENT },
      { w: 0.85, y: 0.19, color: '#ffffff' },
      { w: 0.4, y: 0.08, color: ACCENT },
      { w: 0.95, y: -0.03, color: '#ffffff' },
      { w: 0.65, y: -0.14, color: '#ffffff' },
      { w: 0.3, y: -0.25, color: ACCENT },
    ],
    [],
  )

  useFrame((state) => {
    const t = state.clock.getElapsedTime()
    const cycle = 3.4
    groupRefs.current.forEach((g, i) => {
      if (!g) return
      const phase = i * 0.32
      const local = ((t + phase) % cycle) / cycle
      const reveal = local < 0.55 ? THREE.MathUtils.smoothstep(local / 0.55, 0, 1) : 1
      const scale = local > 0.9 ? 0.001 : Math.max(0.001, reveal)
      g.scale.x = scale
    })
  })

  return (
    <group position={[-0.9, 0, 0.02]}>
      {lineDefs.map((l, i) => (
        <group key={i} ref={(el) => (groupRefs.current[i] = el)} position={[0, l.y, 0]}>
          <mesh position={[l.w / 2, 0, 0]}>
            <planeGeometry args={[l.w, 0.032]} />
            <meshBasicMaterial
              color={l.color}
              transparent
              opacity={0.55}
              blending={THREE.AdditiveBlending}
              depthWrite={false}
            />
          </mesh>
        </group>
      ))}
    </group>
  )
}

// Layer 3 - website building: a landing-page wireframe (nav bar, a panel
// of live running code, CTA button).
function BuildingLayer() {
  return (
    <group>
      <RoundedBox args={[1.9, 0.16, 0.015]} radius={0.03} smoothness={4} position={[0, 0.62, 0]}>
        <meshPhysicalMaterial
          color="#ffffff"
          transparent
          opacity={0.12}
          roughness={0.25}
          transmission={0.4}
          thickness={0.2}
        />
      </RoundedBox>
      <RoundedBox args={[1.9, 0.85, 0.015]} radius={0.04} smoothness={4} position={[0, -0.05, 0]}>
        <meshPhysicalMaterial
          color="#ffffff"
          transparent
          opacity={0.08}
          roughness={0.25}
          transmission={0.4}
          thickness={0.2}
        />
      </RoundedBox>
      <TypingCode />
      <RoundedBox args={[0.55, 0.16, 0.02]} radius={0.05} smoothness={4} position={[0.6, -0.42, 0.02]}>
        <meshBasicMaterial
          color={ACCENT}
          transparent
          opacity={0.85}
          blending={THREE.AdditiveBlending}
          depthWrite={false}
        />
      </RoundedBox>
    </group>
  )
}

// Layer 4 (top) - the personal connection: two nodes joined by a direct
// line with a message pulse traveling between them, standing in for the
// direct WhatsApp relationship and CRM/lead flow - "קשר ישיר".
function ConnectionLayer() {
  const pulseRef = useRef(null)
  const nodeARef = useRef(null)
  const nodeBRef = useRef(null)
  const left = useMemo(() => [-0.65, 0, 0], [])
  const right = useMemo(() => [0.65, 0, 0], [])

  useFrame((state) => {
    const t = state.clock.getElapsedTime()
    if (pulseRef.current) {
      const cycle = (t * 0.35) % 1
      pulseRef.current.position.x = THREE.MathUtils.lerp(left[0], right[0], cycle)
      pulseRef.current.material.opacity = 0.9 * Math.sin(cycle * Math.PI)
    }
    ;[nodeARef, nodeBRef].forEach((ref, i) => {
      if (!ref.current) return
      ref.current.scale.setScalar(1 + Math.sin(t * 2 + i * Math.PI) * 0.12)
    })
  })

  return (
    <group>
      <Line points={[left, right]} color={ACCENT} lineWidth={1} transparent opacity={0.35} />
      <mesh ref={nodeARef} position={left}>
        <circleGeometry args={[0.06, 20]} />
        <meshBasicMaterial color="#ffffff" transparent opacity={0.55} depthWrite={false} />
      </mesh>
      <mesh ref={nodeBRef} position={right}>
        <circleGeometry args={[0.06, 20]} />
        <meshBasicMaterial
          color={ACCENT}
          transparent
          opacity={0.9}
          blending={THREE.AdditiveBlending}
          depthWrite={false}
        />
      </mesh>
      <mesh ref={pulseRef} position={left}>
        <circleGeometry args={[0.035, 16]} />
        <meshBasicMaterial
          color={ACCENT}
          transparent
          opacity={0}
          blending={THREE.AdditiveBlending}
          depthWrite={false}
        />
      </mesh>
    </group>
  )
}

function GlassPlane({ width, height }) {
  return (
    <mesh>
      <planeGeometry args={[width, height]} />
      <meshPhysicalMaterial
        color="#e6faf0"
        transparent
        opacity={0.045}
        roughness={0.1}
        transmission={0.95}
        thickness={0.25}
        ior={1.15}
        side={THREE.DoubleSide}
      />
    </mesh>
  )
}

const LAYER_DEFS = [
  { z: -1.05, size: [3.0, 1.95], Content: MonitoringLayer },
  { z: -0.35, size: [2.9, 1.9], Content: AuditLayer },
  { z: 0.35, size: [2.75, 1.85], Content: BuildingLayer },
  { z: 1.05, size: [2.5, 1.7], Content: ConnectionLayer },
]

// A soft radial burst plus the "LF" mark, both appearing only at the peak
// of the merge cycle - the moment all four layers (monitoring, audits,
// building, and the personal connection) resolve into one: LandingFlow,
// one person behind everything.
function MergeReveal({ mergeState }) {
  const flareRef = useRef(null)
  const textRef = useRef(null)
  useFrame(() => {
    const v = Math.max(0, (mergeState.current.value - 0.8) / 0.2)
    if (flareRef.current) {
      flareRef.current.material.opacity = v * 0.3
      flareRef.current.scale.setScalar(0.5 + v * 0.85)
    }
    if (textRef.current) {
      textRef.current.fillOpacity = v
      textRef.current.scale.setScalar(0.85 + v * 0.15)
    }
  })
  return (
    <group>
      <mesh ref={flareRef} position={[0, 0, 0]}>
        <circleGeometry args={[1, 40]} />
        <meshBasicMaterial
          color={ACCENT}
          transparent
          opacity={0}
          blending={THREE.AdditiveBlending}
          depthWrite={false}
        />
      </mesh>
      <Text
        ref={textRef}
        position={[0, 0, 0.05]}
        fontSize={0.32}
        fontWeight={800}
        color={ACCENT}
        fillOpacity={0}
        anchorX="center"
        anchorY="middle"
      >
        LF
      </Text>
    </group>
  )
}

function ExplodedLayers() {
  const groupRef = useRef(null)
  const layerRefs = useRef([])
  const connectorRefs = useRef([])
  const parallax = useRef({ x: 0, y: 0 })
  const mergeState = useRef({ value: 0 })

  useEffect(() => {
    const tl = gsap.timeline({ repeat: -1, repeatDelay: 5.5 })
    tl.to(mergeState.current, { value: 1, duration: 1.5, ease: 'power2.inOut' })
      .to(mergeState.current, { value: 1, duration: 0.8 })
      .to(mergeState.current, { value: 0, duration: 1.5, ease: 'power2.inOut' })
    return () => tl.kill()
  }, [])

  useFrame((state) => {
    const t = state.clock.getElapsedTime()
    const maxTilt = THREE.MathUtils.degToRad(4)
    const targetX = state.pointer.x * maxTilt
    const targetY = -state.pointer.y * maxTilt

    parallax.current.x = THREE.MathUtils.lerp(parallax.current.x, targetX, 0.04)
    parallax.current.y = THREE.MathUtils.lerp(parallax.current.y, targetY, 0.04)

    if (groupRef.current) {
      const autoRotationY = (t / 40) * Math.PI * 2
      groupRef.current.rotation.y = autoRotationY + parallax.current.x
      groupRef.current.rotation.x = THREE.MathUtils.degToRad(-8) + parallax.current.y
    }

    const merge = mergeState.current.value
    layerRefs.current.forEach((m, i) => {
      if (!m) return
      const phase = i * 1.35
      const bob = Math.sin(t * 0.55 + phase) * 0.045 * (1 - merge)
      m.position.y = bob
      m.position.z = THREE.MathUtils.lerp(LAYER_DEFS[i].z, 0, merge)
    })
    connectorRefs.current.forEach((line) => {
      if (!line) return
      line.material.opacity = 0.25 * (1 - merge)
    })
  })

  // corner connector lines linking the bottom layer to the top layer
  const connectorPairs = useMemo(() => {
    const bottom = LAYER_DEFS[0]
    const top = LAYER_DEFS[LAYER_DEFS.length - 1]
    const [bw, bh] = bottom.size
    const [tw, th] = top.size
    const corners = [
      [-1, -1],
      [1, -1],
      [1, 1],
      [-1, 1],
    ]
    return corners.map(([sx, sy]) => [
      [(sx * bw) / 2, (sy * bh) / 2, bottom.z],
      [(sx * tw) / 2, (sy * th) / 2, top.z],
    ])
  }, [])

  return (
    <group ref={groupRef} rotation={[THREE.MathUtils.degToRad(-8), 0, 0]}>
      {connectorPairs.map((pts, i) => (
        <Line
          key={i}
          ref={(el) => (connectorRefs.current[i] = el)}
          points={pts}
          color={ACCENT}
          lineWidth={0.6}
          transparent
          opacity={0.25}
        />
      ))}
      <MergeReveal mergeState={mergeState} />
      {LAYER_DEFS.map((layer, i) => (
        <group key={i} ref={(el) => (layerRefs.current[i] = el)} position={[0, 0, layer.z]}>
          <GlassPlane width={layer.size[0]} height={layer.size[1]} />
          <layer.Content />
        </group>
      ))}
    </group>
  )
}

function SceneLighting() {
  return (
    <>
      <ambientLight intensity={0.35} />
      <pointLight position={[2, 3, 3]} intensity={18} color={ACCENT} distance={12} decay={2} />
      <pointLight position={[-2, -1, -2]} intensity={4} color="#ffffff" distance={10} decay={2} />
    </>
  )
}

export default function HeroScene({ onContextLost }) {
  // EffectComposer reads getContextAttributes() from the raw WebGL context
  // during setup and throws if that's null - which can happen if the
  // canvas is created while the tab is backgrounded. Only mount
  // postprocessing once we've confirmed the context is actually usable;
  // otherwise render the scene without bloom rather than crashing it.
  const [glReady, setGlReady] = useState(false)

  return (
    <Canvas
      dpr={[1, 1.5]}
      camera={{ position: [0, 0, 6.2], fov: 42 }}
      gl={{ antialias: true, alpha: true }}
      onCreated={({ gl }) => {
        gl.domElement.addEventListener(
          'webglcontextlost',
          (e) => {
            e.preventDefault()
            onContextLost?.()
          },
          { once: true },
        )
        const ctx = gl.getContext?.()
        setGlReady(!!ctx && !!ctx.getContextAttributes())
      }}
    >
      <SceneLighting />
      <ExplodedLayers />
      {glReady && (
        <EffectComposer multisampling={0}>
          <Bloom luminanceThreshold={0.35} luminanceSmoothing={0.9} intensity={0.55} mipmapBlur />
        </EffectComposer>
      )}
    </Canvas>
  )
}
