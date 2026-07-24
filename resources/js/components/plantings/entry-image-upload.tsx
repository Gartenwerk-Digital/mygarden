import { ImageUpload } from '@/components/mygarden/image-upload';

type Props = {
    files: File[];
    onChange: (files: File[]) => void;
    maxFiles?: number;
    disabled?: boolean;
};

/**
 * Thin wrapper around ImageUpload with timeline-specific defaults:
 * 3 MB per file, up to maxFiles images per upload batch.
 */
export function EntryImageUpload({
    files,
    onChange,
    maxFiles = 5,
    disabled = false,
}: Props) {
    return (
        <ImageUpload
            files={files}
            onChange={onChange}
            maxFiles={maxFiles}
            disabled={disabled}
            accept="image/jpeg,image/png,image/webp"
            hint={`max. 3 MB je Bild · bis zu ${maxFiles} Bilder auf einmal`}
        />
    );
}
