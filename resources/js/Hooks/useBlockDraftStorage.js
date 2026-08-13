import { useEffect, useRef, useState } from 'react';

export const useBlockDraftStorage = (storageKey, initialBlocks = []) => {
  const [blocks, setBlocks] = useState(() => {
    const saved = localStorage.getItem(storageKey);
    if (saved) {
      try {
        return JSON.parse(saved);
      } catch (e) {
        console.error('Failed to parse draft storage:', e);
      }
    }
    return initialBlocks;
  });

  const [lastSaved, setLastSaved] = useState(null);
  const timeoutRef = useRef(null);

  useEffect(() => {
    if (timeoutRef.current) clearTimeout(timeoutRef.current);

    timeoutRef.current = setTimeout(() => {
      localStorage.setItem(storageKey, JSON.stringify(blocks));
      setLastSaved(new Date());
    }, 300);

    return () => {
      if (timeoutRef.current) clearTimeout(timeoutRef.current);
    };
  }, [blocks, storageKey]);

  const clearDraft = () => {
    try {
      localStorage.removeItem(storageKey);
      if (typeof window !== 'undefined' && window.localStorage) {
        Object.keys(window.localStorage).forEach((key) => {
          if (key.startsWith('report_draft_blocks_') || key.startsWith('stt_report_draft')) {
            window.localStorage.removeItem(key);
          }
        });
      }
    } catch (e) {
      console.error('Failed to clear draft storage:', e);
    }
    setLastSaved(null);
  };

  return { blocks, setBlocks, lastSaved, clearDraft };
};
