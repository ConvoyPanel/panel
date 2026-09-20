import { UploadedDisk } from '@/features/images/uploads/api'
import useImageDiskUpload from '@/features/images/uploads/use-image-disk-upload'
import { formatBytes } from '@/features/servers/storage/api.ts'

import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { LinearProgressBar } from '@/components/ui/Progress'

interface Props {
    onUploaded: (disk: UploadedDisk) => void
}

/**
 * Hands one disk image to the panel to host.
 *
 * The file travels in chunks, so the controls are the ones a multi-gigabyte
 * transfer actually needs: how far it has got, a way to stop, and a way to pick
 * up from where a dropped connection left it rather than from zero.
 */
const DiskUploadField = ({ onUploaded }: Props) => {
    const upload = useImageDiskUpload({ onComplete: onUploaded })

    return (
        <div className={'space-y-2'}>
            <Input
                type={'file'}
                accept={'.qcow2,.img,.raw'}
                disabled={upload.isBusy}
                onChange={event => {
                    const file = event.target.files?.[0]
                    if (file) void upload.start(file)
                }}
            />

            {upload.status !== 'idle' && upload.status !== 'done' && (
                <>
                    <LinearProgressBar value={upload.percent} />
                    <div className={'flex items-center gap-2'}>
                        <p className={'text-muted-foreground grow text-xs'}>
                            {upload.status === 'finalizing'
                                ? 'Checking the file'
                                : `${formatBytes(upload.sent)} of ${formatBytes(upload.total)}`}
                        </p>
                        {upload.status === 'interrupted' && (
                            <Button
                                type={'button'}
                                variant={'secondary'}
                                size={'sm'}
                                onClick={() => void upload.resume()}
                            >
                                Resume
                            </Button>
                        )}
                        <Button
                            type={'button'}
                            variant={'ghost'}
                            size={'sm'}
                            onClick={() => void upload.cancel()}
                        >
                            Cancel
                        </Button>
                    </div>
                </>
            )}

            {upload.error && (
                <p className={'text-destructive text-xs'}>{upload.error}</p>
            )}
        </div>
    )
}

export default DiskUploadField
