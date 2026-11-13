import { Fragment, ReactNode } from 'react'

/**
 * Where an announcement may link to, the same list the panel checks on the
 * server (Convoy\Services\Announcements\Links). The body has already had other
 * links taken out; this is the second lock.
 */
export const isAllowedLink = (url: string): boolean => {
    try {
        const parsed = new URL(url)

        if (
            parsed.protocol !== 'https:' ||
            parsed.username ||
            parsed.password ||
            parsed.port
        ) {
            return false
        }

        return (
            (parsed.hostname === 'convoypanel.com' &&
                url.startsWith('https://convoypanel.com')) ||
            (parsed.hostname === 'github.com' &&
                url.startsWith('https://github.com/ConvoyPanel/'))
        )
    } catch {
        return false
    }
}

const TOKEN = /(`[^`\n]+`)|\[([^\]\n]*)\]\(([^)\s]*)\)/g

const renderInline = (text: string, linkClassName: string): ReactNode[] => {
    const nodes: ReactNode[] = []
    let last = 0

    for (const match of text.matchAll(TOKEN)) {
        const index = match.index ?? 0

        if (index > last) {
            nodes.push(text.slice(last, index))
        }

        if (match[1]) {
            nodes.push(
                <code
                    key={index}
                    className='rounded bg-black/10 px-1 py-0.5 font-mono text-[0.85em] dark:bg-white/10'
                >
                    {match[1].slice(1, -1)}
                </code>
            )
        } else if (isAllowedLink(match[3])) {
            nodes.push(
                <a
                    key={index}
                    href={match[3]}
                    target='_blank'
                    rel='noopener noreferrer'
                    className={linkClassName}
                >
                    {match[2]}
                </a>
            )
        } else {
            nodes.push(match[2])
        }

        last = index + match[0].length
    }

    if (last < text.length) {
        nodes.push(text.slice(last))
    }

    return nodes
}

interface Props {
    body: string
    className?: string
    linkClassName?: string
}

/**
 * An announcement's body: short Markdown rendered with links and inline code
 * only (docs/announcements-feed.md). Everything else, HTML included, shows as
 * the text it is.
 */
const AnnouncementBody = ({
    body,
    className,
    linkClassName = 'underline underline-offset-2',
}: Props) => (
    <div className={className}>
        {body
            .split(/\n{2,}/)
            .filter(paragraph => paragraph.trim() !== '')
            .map((paragraph, index) => (
                <p key={index} className={index > 0 ? 'mt-2' : undefined}>
                    {paragraph.split('\n').map((line, lineIndex) => (
                        <Fragment key={lineIndex}>
                            {lineIndex > 0 && <br />}
                            {renderInline(line, linkClassName)}
                        </Fragment>
                    ))}
                </p>
            ))}
    </div>
)

export default AnnouncementBody
