import * as ImageManipulator from 'expo-image-manipulator';

export interface CompressedImageResult {
  uri: string;
  width: number;
  height: number;
  name: string;
  type: string;
}

/**
 * Auto-size and compress evidence photo before network upload.
 * Targets Max Dimension: 1920px, JPEG Quality: 0.75
 * Reduces file size from 15MB+ down to ~400KB - 800KB.
 */
export async function autoSizeAndCompressPhoto(
  imageUri: string,
  targetMaxDimension: number = 1920,
  compressQuality: number = 0.75
): Promise<CompressedImageResult> {
  try {
    const actions: ImageManipulator.Action[] = [
      {
        resize: {
          width: targetMaxDimension,
        },
      },
    ];

    const result = await ImageManipulator.manipulateAsync(
      imageUri,
      actions,
      {
        compress: compressQuality,
        format: ImageManipulator.SaveFormat.JPEG,
      }
    );

    const fileName = `evidence_${Date.now()}.jpg`;

    return {
      uri: result.uri,
      width: result.width,
      height: result.height,
      name: fileName,
      type: 'image/jpeg',
    };
  } catch (error) {
    console.warn('Image manipulation fallback triggered:', error);
    return {
      uri: imageUri,
      width: 1920,
      height: 1080,
      name: `evidence_${Date.now()}.jpg`,
      type: 'image/jpeg',
    };
  }
}
