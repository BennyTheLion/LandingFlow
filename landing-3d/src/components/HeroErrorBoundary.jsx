import { Component } from 'react'

// Class component required - React has no hook-based error boundary API.
// Catches render/setup errors from the 3D canvas (e.g. EffectComposer
// throwing when the WebGL context isn't fully ready yet, which can happen
// if the tab is backgrounded at creation time) and falls back to the
// static hero background instead of leaving a blank section.
export default class HeroErrorBoundary extends Component {
  constructor(props) {
    super(props)
    this.state = { hasError: false }
  }

  static getDerivedStateFromError() {
    return { hasError: true }
  }

  componentDidCatch(error) {
    // eslint-disable-next-line no-console
    console.error('Hero 3D scene failed, falling back to static background:', error)
  }

  componentDidUpdate(prevProps) {
    if (this.state.hasError && prevProps.resetKey !== this.props.resetKey) {
      this.setState({ hasError: false })
    }
  }

  render() {
    if (this.state.hasError) {
      return this.props.fallback
    }
    return this.props.children
  }
}
