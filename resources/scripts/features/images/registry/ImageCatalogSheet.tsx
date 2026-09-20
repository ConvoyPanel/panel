import { imageGroupQueries } from '@/features/images/api.ts'
import ImageIconDisplay from '@/features/images/components/ImageIconDisplay.tsx'
import {
    importRegistryTemplates,
    registryQueries,
    useRegistryCatalog,
} from '@/features/images/registry/api.ts'
import { formatBytes } from '@/features/servers/storage/api.ts'
import {
    ImageIcon,
    RegistryGroup,
    RegistryImportStatus,
    RegistryTemplate,
} from '@/types/image.ts'
import { IconDisc, IconRefresh } from '@tabler/icons-react'
import { useMutation, useQueryClient } from '@tanstack/react-query'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemGroup,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/Item'
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/Sheet'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'

interface Props {
    open: boolean
    onOpenChange: (open: boolean) => void
}

/**
 * The icon the panel would give a group of this name, so a catalogue row looks
 * like the image it becomes.
 */
const iconFor = (slug: string): ImageIcon | null => {
    const normalized = slug.replace(/-/g, '_')

    return (
        Object.values(ImageIcon).find(
            icon => normalized === icon || normalized.startsWith(icon)
        ) ?? (normalized.startsWith('windows') ? ImageIcon.WINDOWS : null)
    )
}

/**
 * Browses a published catalogue and copies entries into the panel.
 *
 * Reads as the images list it feeds, because that is what an entry becomes:
 * importing writes an ordinary image group, definition and version, which the
 * operator then owns outright.
 */
const ImageCatalogSheet = ({ open, onOpenChange }: Props) => {
    const queryClient = useQueryClient()
    const { data: catalog, isLoading, isError, refetch } = useRegistryCatalog()

    const { mutateAsync, isPending, variables } = useMutation({
        mutationFn: (template: RegistryTemplate) =>
            importRegistryTemplates([template.slug]),
        onSuccess: async results => {
            await Promise.all([
                queryClient.invalidateQueries({
                    queryKey: imageGroupQueries.lists(),
                }),
                queryClient.invalidateQueries({
                    queryKey: registryQueries.all(),
                }),
            ])

            const result = results[0]

            toast.add({
                title: result?.created
                    ? `Imported version ${result.version}`
                    : 'This build is already here',
                type: 'success',
            })
        },
        onError: () => {
            toast.add({ title: 'Failed to import', type: 'error' })
        },
    })

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                className={'w-11/12 overflow-y-auto sm:max-w-lg'}
                side={'right'}
            >
                <SheetHeader>
                    <SheetTitle className={'truncate'}>
                        {catalog?.name ?? 'Image catalogue'}
                    </SheetTitle>
                </SheetHeader>

                <div className={'mb-4 flex items-center gap-2'}>
                    <p
                        className={
                            'text-muted-foreground min-w-0 grow truncate text-xs'
                        }
                    >
                        {catalog?.url}
                    </p>
                    <Button
                        variant={'secondary'}
                        size={'sm'}
                        onClick={() => void refetch()}
                    >
                        <IconRefresh className={'size-4'} /> Refresh
                    </Button>
                </div>

                {isError ? (
                    <Card className={'py-6'}>
                        <CollectionErrorState
                            onRetry={refetch}
                            title={'Couldn’t read the catalogue'}
                            description={
                                'Check that the panel can reach it, then try again.'
                            }
                        />
                    </Card>
                ) : isLoading ? (
                    <div className={'flex flex-col gap-2'}>
                        {Array.from({ length: 5 }).map((_, index) => (
                            <Skeleton key={index} className={'h-16'} />
                        ))}
                    </div>
                ) : !catalog || catalog.groups.length === 0 ? (
                    <Card className={'py-6'}>
                        <SimpleEmptyState
                            icon={IconDisc}
                            title={'No images published'}
                            description={
                                'Point the panel at a catalogue that lists some.'
                            }
                        />
                    </Card>
                ) : (
                    <div className={'flex flex-col gap-4'}>
                        {catalog.groups.map((group: RegistryGroup) => (
                            <div key={group.slug} className={'space-y-2'}>
                                <p className={'text-sm font-semibold'}>
                                    {group.name}
                                </p>
                                <ItemGroup className={'gap-2'}>
                                    {group.templates.map(template => (
                                        <CatalogRow
                                            key={template.slug}
                                            template={template}
                                            busy={
                                                isPending &&
                                                variables?.slug ===
                                                    template.slug
                                            }
                                            onImport={() =>
                                                void mutateAsync(template)
                                            }
                                        />
                                    ))}
                                </ItemGroup>
                            </div>
                        ))}
                    </div>
                )}
            </SheetContent>
        </Sheet>
    )
}

const CatalogRow = ({
    template,
    busy,
    onImport,
}: {
    template: RegistryTemplate
    busy: boolean
    onImport: () => void
}) => (
    <Item variant={'muted'} size={'sm'}>
        <ItemMedia variant={'icon'}>
            <ImageIconDisplay
                icon={iconFor(template.groupSlug)}
                defaultIcon={IconDisc}
                className={'size-4'}
            />
        </ItemMedia>
        <ItemContent className={'min-w-0 overflow-x-hidden'}>
            <ItemTitle className={'max-w-full'}>
                <span className={'truncate'}>{template.display}</span>
                {template.status === RegistryImportStatus.IMPORTED && (
                    <Badge variant={'secondary'} className={'shrink-0'}>
                        Imported
                    </Badge>
                )}
                {template.status === RegistryImportStatus.UPDATE_AVAILABLE && (
                    <Badge className={'shrink-0'}>Rebuilt</Badge>
                )}
            </ItemTitle>
            <ItemDescription className={'truncate'}>
                {template.version} · {formatBytes(template.size)} to transfer ·{' '}
                {formatBytes(template.minimumDisk)} provisioned
                {template.importedVersion &&
                    ` · you have ${template.importedVersion}`}
            </ItemDescription>
        </ItemContent>
        <ItemActions className={'ml-auto'}>
            <Button
                size={'sm'}
                variant={
                    template.status === RegistryImportStatus.UPDATE_AVAILABLE
                        ? 'default'
                        : 'secondary'
                }
                disabled={
                    busy || template.status === RegistryImportStatus.IMPORTED
                }
                onClick={onImport}
            >
                {template.status === RegistryImportStatus.UPDATE_AVAILABLE
                    ? 'Update'
                    : 'Import'}
            </Button>
        </ItemActions>
    </Item>
)

export default ImageCatalogSheet
