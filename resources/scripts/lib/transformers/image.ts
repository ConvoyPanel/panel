import type {
    ImageDefinition,
    ImageGroup,
    ImageVersion,
} from '@/types/image.ts'

export const rawDataToImageVersion = (data: any): ImageVersion =>
    data as ImageVersion

export const rawDataToImageDefinition = (raw: any): ImageDefinition => ({
    ...(raw as ImageDefinition),
    latestVersion: raw.latestVersion
        ? rawDataToImageVersion(raw.latestVersion)
        : null,
    versions: raw.versions?.map(rawDataToImageVersion),
})

export const rawDataToImageGroup = (raw: any): ImageGroup => ({
    ...(raw as ImageGroup),
    definitions: raw.definitions?.map(rawDataToImageDefinition),
})
