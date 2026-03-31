import { useState, useCallback, useRef, useEffect } from 'react';

export default function ProductAutocomplete({ value, onChange, searchUrl }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [showDropdown, setShowDropdown] = useState(false);
    const [tags, setTags] = useState(() => {
        if (!value) return [];
        return value.split(',').map((v) => v.trim()).filter(Boolean);
    });
    const debounceRef = useRef(null);
    const wrapperRef = useRef(null);

    // Close dropdown on outside click
    useEffect(() => {
        const handleClick = (e) => {
            if (wrapperRef.current && !wrapperRef.current.contains(e.target)) {
                setShowDropdown(false);
            }
        };
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, []);

    // Sync tags back to parent
    const updateParent = useCallback((newTags) => {
        setTags(newTags);
        onChange(newTags.join(','));
    }, [onChange]);

    const search = useCallback((q) => {
        if (!searchUrl || q.length < 2) {
            setResults([]);
            return;
        }
        clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(async () => {
            try {
                const res = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`);
                if (res.ok) {
                    const data = await res.json();
                    setResults(data);
                    setShowDropdown(true);
                }
            } catch (e) {
                setResults([]);
            }
        }, 250);
    }, [searchUrl]);

    const handleInput = (e) => {
        const v = e.target.value;
        setQuery(v);
        search(v);
    };

    const addTag = (code) => {
        if (!tags.includes(code)) {
            updateParent([...tags, code]);
        }
        setQuery('');
        setResults([]);
        setShowDropdown(false);
    };

    const removeTag = (code) => {
        updateParent(tags.filter((t) => t !== code));
    };

    return (
        <div ref={wrapperRef} style={{ position: 'relative' }}>
            {/* Tags */}
            {tags.length > 0 && (
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: '4px', marginBottom: '8px' }}>
                    {tags.map((tag) => (
                        <span key={tag} style={{
                            display: 'inline-flex', alignItems: 'center', gap: '4px',
                            padding: '3px 8px', background: '#EFF6FF', border: '1px solid #BFDBFE',
                            borderRadius: '6px', fontSize: '12px', color: '#1E40AF',
                        }}>
                            {tag}
                            <button onClick={() => removeTag(tag)} style={{
                                background: 'none', border: 'none', cursor: 'pointer',
                                fontSize: '14px', color: '#6B7280', padding: 0, lineHeight: 1,
                            }}>×</button>
                        </span>
                    ))}
                </div>
            )}

            {/* Input */}
            <input
                className="swp-panel__input"
                type="text"
                value={query}
                onChange={handleInput}
                placeholder="Search products..."
                onFocus={() => { if (results.length > 0) setShowDropdown(true); }}
            />

            {/* Dropdown */}
            {showDropdown && results.length > 0 && (
                <div style={{
                    position: 'absolute', top: '100%', left: 0, right: 0, marginTop: '4px',
                    background: '#fff', border: '1px solid #E5E7EB', borderRadius: '8px',
                    boxShadow: '0 8px 24px rgba(0,0,0,0.08)', zIndex: 100, maxHeight: '200px',
                    overflowY: 'auto', padding: '4px',
                }}>
                    {results.map((item) => (
                        <button
                            key={item.code}
                            onClick={() => addTag(item.code)}
                            style={{
                                display: 'block', width: '100%', padding: '8px 10px', border: 'none',
                                borderRadius: '6px', background: 'none', textAlign: 'left', cursor: 'pointer',
                                fontSize: '13px',
                            }}
                            onMouseEnter={(e) => { e.target.style.background = '#F3F4F6'; }}
                            onMouseLeave={(e) => { e.target.style.background = 'none'; }}
                        >
                            <strong>{item.code}</strong>
                            <span style={{ color: '#6B7280', marginLeft: '8px' }}>{item.name}</span>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
