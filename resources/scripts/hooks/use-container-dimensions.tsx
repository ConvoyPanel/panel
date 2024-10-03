import { useCallback, useLayoutEffect, useState } from 'react'

const useContainerDimensions = () => {
    const [dimensions, setDimensions] = useState({ width: 0, height: 0 })
    const [container, setContainer] = useState<HTMLDivElement | null>(null)

    const containerRef = useCallback((containerElem: HTMLDivElement) => {
        if (containerElem !== null) {
            const { width, height } = containerElem.getBoundingClientRect()
            setDimensions({ width, height })
            setContainer(containerElem)
        }
    }, [])

    const handleResize = useCallback(() => {
        if (container) {
            const { width, height } = container.getBoundingClientRect()
            setDimensions({ width, height })
        }
    }, [container])

    useLayoutEffect(() => {
        window.addEventListener('resize', handleResize)

        return () => {
            window.removeEventListener('resize', handleResize)
        }
    }, [handleResize])

    return { containerRef, dimensions }
}

export default useContainerDimensions
