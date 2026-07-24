import { ImagePlus, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';

type Props = {
    files: File[];
    onChange: (files: File[]) => void;
    accept?: string;
    maxFiles?: number;
    hint?: string;
    disabled?: boolean;
};

export function ImageUpload({
    files,
    onChange,
    accept = 'image/jpeg,image/png,image/webp',
    maxFiles = 5,
    hint,
    disabled = false,
}: Props) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [previewUrls, setPreviewUrls] = useState<string[]>([]);
    // Tracks all live URLs for unmount cleanup – read only outside render
    const urlRegistryRef = useRef<string[]>([]);

    // Revoke all remaining object URLs on unmount
    useEffect(() => {
        return () => {
            urlRegistryRef.current.forEach((url) => URL.revokeObjectURL(url));
        };
    }, []);

    function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
        const selected = Array.from(e.target.files ?? []);
        const newUrls = selected.map((f) => {
            const url = URL.createObjectURL(f);
            urlRegistryRef.current.push(url);

            return url;
        });

        const combinedFiles = [...files, ...selected].slice(0, maxFiles);
        const combinedUrls = [...previewUrls, ...newUrls].slice(0, maxFiles);

        onChange(combinedFiles);
        setPreviewUrls(combinedUrls);

        if (inputRef.current) {
            inputRef.current.value = '';
        }
    }

    function removeFile(index: number) {
        const url = previewUrls[index];

        if (url) {
            URL.revokeObjectURL(url);
            urlRegistryRef.current = urlRegistryRef.current.filter(
                (u) => u !== url,
            );
        }

        onChange(files.filter((_, i) => i !== index));
        setPreviewUrls((prev) => prev.filter((_, i) => i !== index));
    }

    return (
        <div className="space-y-3">
            {previewUrls.length > 0 && (
                <div className="flex flex-wrap gap-2">
                    {previewUrls.map((url, index) => (
                        <div key={index} className="group relative">
                            <img
                                src={url}
                                alt=""
                                className="h-20 w-20 rounded-md border border-border object-cover"
                            />
                            <button
                                type="button"
                                onClick={() => removeFile(index)}
                                disabled={disabled}
                                className="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-destructive-foreground opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                aria-label="Bild entfernen"
                            >
                                <X className="size-3" />
                            </button>
                        </div>
                    ))}
                </div>
            )}

            {files.length < maxFiles && (
                <div>
                    <input
                        ref={inputRef}
                        type="file"
                        multiple
                        accept={accept}
                        className="sr-only"
                        onChange={handleFileChange}
                        disabled={disabled}
                    />
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => inputRef.current?.click()}
                        disabled={disabled}
                        asChild={false}
                    >
                        <ImagePlus className="size-4" />
                        Bilder auswählen
                    </Button>
                    {hint && (
                        <p className="mt-1.5 text-xs text-muted-foreground">
                            {hint}
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
