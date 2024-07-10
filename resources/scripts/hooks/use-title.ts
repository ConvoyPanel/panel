import { useRouterState } from '@tanstack/react-router'
import { useEffect } from 'react'


const useTitle = (middleText?: string) => {
    const routeMeta = useRouterState({
        select: state => {
            return state.matches.map(match => match.meta!).filter(Boolean)
        },
    })

    useEffect(() => {
        let title = null
        if (routeMeta.length > 0) {
            const lastMeta = routeMeta[routeMeta.length - 1]
            const titleTag = lastMeta.find(tag => tag.title)
            title = titleTag ? titleTag.title : null
        }

        const fullTitle = title
            ? middleText
                ? `${title} | ${middleText} | Convoy`
                : `${title} | Convoy`
            : 'Convoy'

        document.title = fullTitle
    }, [routeMeta, middleText])
}

export default useTitle
