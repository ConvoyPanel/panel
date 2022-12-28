import { useLayoutEffect, useEffect } from 'react'
import { isServer } from './ssr'

export let useIsoMorphicEffect = isServer ? useEffect : useLayoutEffect