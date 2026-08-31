import { useState, useEffect, useRef, useCallback } from 'react';

/**
 * Custom hook for automatic draft saving to browser localStorage.
 * Prevents data loss when creating or editing company documents.
 * Automatically clears draft upon successful save.
 */
export function useDocumentDraft({
  storageKey,
  data,
  setData,
  initialData = {},
  isEdit = false,
  debounceMs = 600,
}) {
  const [draftDetected, setDraftDetected] = useState(false);
  const [savedDraftData, setSavedDraftData] = useState(null);
  const [lastSavedTime, setLastSavedTime] = useState(null);
  const isInitialMount = useRef(true);
  const isRestoring = useRef(false);
  const saveTimeoutRef = useRef(null);

  // Check for existing draft on initial mount
  useEffect(() => {
    try {
      const stored = localStorage.getItem(storageKey);
      if (stored) {
        const parsed = JSON.parse(stored);
        const hasContent = Boolean(
          (parsed.title && parsed.title.trim()) ||
          (parsed.body && parsed.body !== '<p></p>' && parsed.body.trim())
        );

        if (hasContent) {
          // If in edit mode, only trigger if draft differs from server document
          if (isEdit) {
            const hasDifferences =
              parsed.title !== (initialData.title || '') ||
              parsed.body !== (initialData.body || '') ||
              String(parsed.department_id || '') !== String(initialData.department_id || '') ||
              String(parsed.company_document_type_id || '') !== String(initialData.company_document_type_id || '');

            if (hasDifferences) {
              setSavedDraftData(parsed);
              setDraftDetected(true);
            }
          } else {
            // In create mode
            setSavedDraftData(parsed);
            setDraftDetected(true);
          }
        }
      }
    } catch (e) {
      console.warn('Failed to read draft from localStorage:', e);
    }
  }, [storageKey, isEdit]);

  // Debounced auto-save effect
  useEffect(() => {
    if (isInitialMount.current) {
      isInitialMount.current = false;
      return;
    }

    if (isRestoring.current) {
      isRestoring.current = false;
      return;
    }

    if (saveTimeoutRef.current) {
      clearTimeout(saveTimeoutRef.current);
    }

    saveTimeoutRef.current = setTimeout(() => {
      try {
        const hasContent = Boolean(
          (data.title && data.title.trim()) ||
          (data.body && data.body !== '<p></p>' && data.body.trim())
        );

        if (hasContent) {
          const draftPayload = {
            ...data,
            _draftSavedAt: new Date().toISOString(),
          };
          localStorage.setItem(storageKey, JSON.stringify(draftPayload));
          setLastSavedTime(new Date());
        }
      } catch (e) {
        console.warn('Failed to save draft to localStorage:', e);
      }
    }, debounceMs);

    return () => {
      if (saveTimeoutRef.current) {
        clearTimeout(saveTimeoutRef.current);
      }
    };
  }, [data, storageKey, debounceMs]);

  // Restore draft into form state
  const restoreDraft = useCallback(() => {
    if (!savedDraftData) return;
    isRestoring.current = true;

    // Restore form fields
    Object.keys(savedDraftData).forEach((key) => {
      if (key !== '_draftSavedAt' && data[key] !== undefined) {
        setData(key, savedDraftData[key]);
      }
    });

    setDraftDetected(false);
    setLastSavedTime(new Date(savedDraftData._draftSavedAt || Date.now()));
  }, [savedDraftData, data, setData]);

  // Discard draft and remove from localStorage
  const discardDraft = useCallback(() => {
    try {
      localStorage.removeItem(storageKey);
    } catch (e) {
      console.warn('Failed to clear draft:', e);
    }
    setDraftDetected(false);
    setSavedDraftData(null);
    setLastSavedTime(null);
  }, [storageKey]);

  // Clear draft on successful submit
  const clearDraft = useCallback(() => {
    try {
      localStorage.removeItem(storageKey);
    } catch (e) {
      console.warn('Failed to clear draft:', e);
    }
    setDraftDetected(false);
    setSavedDraftData(null);
    setLastSavedTime(null);
  }, [storageKey]);

  return {
    draftDetected,
    draftSavedAt: savedDraftData?._draftSavedAt,
    lastSavedTime,
    restoreDraft,
    discardDraft,
    clearDraft,
  };
}
