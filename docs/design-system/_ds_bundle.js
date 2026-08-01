/* @ds-bundle: {"format":4,"namespace":"NouronDesignSystem_019dc5","components":[{"name":"Button","sourcePath":"components/core/Button.jsx"},{"name":"Card","sourcePath":"components/core/Card.jsx"},{"name":"APChip","sourcePath":"components/data/APChip.jsx"},{"name":"EntityChip","sourcePath":"components/data/EntityChip.jsx"},{"name":"ProgressBar","sourcePath":"components/data/ProgressBar.jsx"},{"name":"ResourceBar","sourcePath":"components/data/ResourceBar.jsx"},{"name":"ResourceChip","sourcePath":"components/data/ResourceChip.jsx"},{"name":"StatusBadge","sourcePath":"components/data/StatusBadge.jsx"},{"name":"Table","sourcePath":"components/data/Table.jsx"},{"name":"Dialog","sourcePath":"components/feedback/Dialog.jsx"},{"name":"Checkbox","sourcePath":"components/forms/Checkbox.jsx"},{"name":"FormField","sourcePath":"components/forms/FormField.jsx"},{"name":"Input","sourcePath":"components/forms/Input.jsx"},{"name":"RangeSlider","sourcePath":"components/forms/RangeSlider.jsx"},{"name":"Select","sourcePath":"components/forms/Select.jsx"},{"name":"Switch","sourcePath":"components/forms/Switch.jsx"},{"name":"Navbar","sourcePath":"components/navigation/Navbar.jsx"},{"name":"SubnavTabs","sourcePath":"components/navigation/SubnavTabs.jsx"}],"sourceHashes":{"components/core/Button.jsx":"4fafff32535e","components/core/Card.jsx":"220de5d15289","components/data/APChip.jsx":"facb5ebe5405","components/data/EntityChip.jsx":"fa1cceaaf6c9","components/data/ProgressBar.jsx":"b61517b9c1b9","components/data/ResourceBar.jsx":"c282d3563cc6","components/data/ResourceChip.jsx":"34c5a443fa14","components/data/StatusBadge.jsx":"d07f5097ff67","components/data/Table.jsx":"3a5bb9da4350","components/feedback/Dialog.jsx":"ccea753dabcf","components/forms/Checkbox.jsx":"8b9472f656cf","components/forms/FormField.jsx":"dc24e4365b6f","components/forms/Input.jsx":"af5353edcc2c","components/forms/RangeSlider.jsx":"26f911a45a51","components/forms/Select.jsx":"48022c2ad30b","components/forms/Switch.jsx":"9f4885b2bc3e","components/navigation/Navbar.jsx":"78c0a4d7f1eb","components/navigation/SubnavTabs.jsx":"173e7c0af82f","ui_kits/colony/ColonyScreen.jsx":"c5d6311afbc6","ui_kits/colony/LobbyScreen.jsx":"e902344d8238"},"inlinedExternals":[],"unexposedExports":[]} */

(() => {
    const __ds_ns = (window.NouronDesignSystem_019dc5 = window.NouronDesignSystem_019dc5 || {});

    const __ds_scope = {};

    __ds_ns.__errors = __ds_ns.__errors || [];

    // components/core/Button.jsx
    try {
        (() => {
            const NOTCH = 12,
                BW = 2;
            const bevel = (n) =>
                `polygon(0 0, calc(100% - ${n}px) 0, 100% ${n}px, 100% 100%, ${n}px 100%, 0 calc(100% - ${n}px))`;
            const BASE = {
                fontFamily: 'var(--font-body)',
                fontWeight: 700,
                fontSize: 'var(--text-button-size)',
                letterSpacing: '0.08em',
                textTransform: 'uppercase',
                clipPath: bevel(NOTCH),
                cursor: 'pointer',
                padding: `${BW}px`,
                border: 'none',
                transition: 'background 0.15s',
                display: 'inline-flex',
                position: 'relative',
                outline: 'none',
                boxSizing: 'border-box',
            };
            const INNER_BASE = {
                clipPath: bevel(NOTCH - BW),
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.4rem',
                padding: '0.6rem 1.2rem',
                width: '100%',
                transition: 'background 0.15s, color 0.15s',
            };
            const VARIANTS = {
                primary: {
                    lineColor: 'var(--color-accent)',
                    fg: 'var(--color-accent)',
                },
                secondary: {
                    lineColor: 'var(--color-text-primary)',
                    fg: 'var(--color-text-primary)',
                },
                ghost: {
                    lineColor: 'var(--color-border-strong)',
                    fg: 'var(--color-text-secondary)',
                },
            };
            function Button({
                variant = 'primary',
                disabled = false,
                apCost = null,
                apType = 'build',
                children,
                onClick,
                style,
            }) {
                const [hover, setHover] = React.useState(false);
                const v = VARIANTS[variant] || VARIANTS.primary;
                let fill = '#fff',
                    color = v.fg;
                if (hover && !disabled) {
                    if (variant === 'primary') {
                        fill = 'var(--color-accent)';
                        color = '#fff';
                    }
                    if (variant === 'secondary') {
                        fill = 'var(--color-text-primary)';
                        color = '#fff';
                    }
                    if (variant === 'ghost') {
                        fill = 'var(--color-surface)';
                    }
                }
                return /*#__PURE__*/ React.createElement(
                    'button',
                    {
                        type: 'button',
                        disabled: disabled,
                        onClick: onClick,
                        onMouseEnter: () => setHover(true),
                        onMouseLeave: () => setHover(false),
                        style: {
                            ...BASE,
                            background: v.lineColor,
                            opacity: disabled ? 0.45 : 1,
                            pointerEvents: disabled ? 'none' : 'auto',
                            width: apCost ? '100%' : undefined,
                            ...style,
                        },
                    },
                    /*#__PURE__*/ React.createElement(
                        'span',
                        {
                            style: {
                                ...INNER_BASE,
                                background: fill,
                                color,
                                justifyContent: apCost ? 'space-between' : 'center',
                            },
                        },
                        /*#__PURE__*/ React.createElement('span', null, children),
                        apCost != null &&
                            /*#__PURE__*/ React.createElement(APBadge, {
                                amount: apCost,
                                type: apType,
                            }),
                    ),
                );
            }
            function APBadge({ amount, type }) {
                const colors = {
                    build: ['var(--ap-build-bg)', 'var(--ap-build-fg)'],
                    nav: ['var(--ap-nav-bg)', 'var(--ap-nav-fg)'],
                    research: ['var(--ap-research-bg)', 'var(--ap-research-fg)'],
                    economy: ['var(--ap-economy-bg)', 'var(--ap-economy-fg)'],
                    strategy: ['var(--ap-strategy-bg)', 'var(--ap-strategy-fg)'],
                };
                const [bg, fg] = colors[type] || colors.build;
                return /*#__PURE__*/ React.createElement(
                    'span',
                    {
                        style: {
                            flexShrink: 0,
                            fontSize: '0.72rem',
                            fontWeight: 600,
                            padding: '0.2rem 0.55rem',
                            borderRadius: 'var(--radius-round)',
                            background: bg,
                            color: fg,
                            whiteSpace: 'nowrap',
                        },
                    },
                    amount,
                    ' AP',
                );
            }
            Object.assign(__ds_scope, { Button });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/core/Button.jsx', error: String((e && e.message) || e) });
    }

    // components/core/Card.jsx
    try {
        (() => {
            function Card({ title, badge, footer, children, style }) {
                const NOTCH = 16,
                    BW = 2;
                const bevel = (n) => `polygon(0 0, calc(100% - ${n}px) 0, 100% ${n}px, 100% 100%, 0 100%)`;
                return /*#__PURE__*/ React.createElement(
                    'div',
                    {
                        style: {
                            position: 'relative',
                            display: 'flex',
                            clipPath: bevel(NOTCH),
                            background: 'var(--color-accent)',
                            padding: `${BW}px`,
                            boxSizing: 'border-box',
                            ...style,
                        },
                    },
                    /*#__PURE__*/ React.createElement(
                        'article',
                        {
                            style: {
                                clipPath: bevel(NOTCH - BW),
                                background: 'var(--color-bg)',
                                padding: 'var(--card-padding)',
                                fontFamily: 'var(--font-body)',
                                flex: 1,
                                minWidth: 0,
                            },
                        },
                        (title || badge) &&
                            /*#__PURE__*/ React.createElement(
                                'header',
                                {
                                    style: {
                                        display: 'flex',
                                        alignItems: 'baseline',
                                        justifyContent: 'space-between',
                                        gap: '0.5rem',
                                        marginBottom: '0.5rem',
                                    },
                                },
                                title &&
                                    /*#__PURE__*/ React.createElement(
                                        'h3',
                                        {
                                            style: {
                                                margin: 0,
                                                fontSize: 'var(--text-h3-size)',
                                                fontWeight: 'var(--text-h3-weight)',
                                                color: 'var(--color-text-primary)',
                                            },
                                        },
                                        title,
                                    ),
                                badge,
                            ),
                        /*#__PURE__*/ React.createElement('div', null, children),
                        footer &&
                            /*#__PURE__*/ React.createElement(
                                'footer',
                                {
                                    style: {
                                        marginTop: '1rem',
                                        display: 'flex',
                                        gap: '0.5rem',
                                        flexWrap: 'wrap',
                                        alignItems: 'center',
                                    },
                                },
                                footer,
                            ),
                    ),
                );
            }
            Object.assign(__ds_scope, { Card });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/core/Card.jsx', error: String((e && e.message) || e) });
    }

    // components/data/APChip.jsx
    try {
        (() => {
            const AP_COLORS = {
                nav: ['var(--ap-nav-bg)', 'var(--ap-nav-fg)'],
                build: ['var(--ap-build-bg)', 'var(--ap-build-fg)'],
                research: ['var(--ap-research-bg)', 'var(--ap-research-fg)'],
                economy: ['var(--ap-economy-bg)', 'var(--ap-economy-fg)'],
                strategy: ['var(--ap-strategy-bg)', 'var(--ap-strategy-fg)'],
                neutral: ['var(--ap-neutral-bg)', 'var(--ap-neutral-fg)'],
            };
            function APChip({ type = 'neutral', children }) {
                const [bg, fg] = AP_COLORS[type] || AP_COLORS.neutral;
                return /*#__PURE__*/ React.createElement(
                    'span',
                    {
                        style: {
                            fontSize: '0.72rem',
                            fontWeight: 600,
                            padding: '0.2rem 0.55rem',
                            borderRadius: 'var(--radius-round)',
                            whiteSpace: 'nowrap',
                            background: bg,
                            color: fg,
                            fontFamily: 'var(--font-body)',
                        },
                    },
                    children,
                );
            }
            Object.assign(__ds_scope, { APChip });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/data/APChip.jsx', error: String((e && e.message) || e) });
    }

    // components/data/EntityChip.jsx
    try {
        (() => {
            const ICONS = {
                building: 'bi-hexagon',
                knowledge: 'bi-book',
                resource: 'bi-layers',
                ship: 'bi-rocket-takeoff',
                advisor: 'bi-person-badge',
                research: 'bi-diagram-3',
            };
            function EntityChip({ type = 'resource', label, level, description }) {
                const [open, setOpen] = React.useState(false);
                return /*#__PURE__*/ React.createElement(
                    'span',
                    {
                        onMouseEnter: () => setOpen(true),
                        onMouseLeave: () => setOpen(false),
                        onClick: () => setOpen((o) => !o),
                        style: {
                            position: 'relative',
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '0.3rem',
                            padding: '2px 8px',
                            borderRadius: '10px',
                            background: 'var(--color-surface)',
                            border: '1px solid var(--color-border)',
                            fontSize: '0.8rem',
                            cursor: 'pointer',
                            fontFamily: 'var(--font-body)',
                        },
                    },
                    /*#__PURE__*/ React.createElement('i', {
                        className: 'bi ' + (ICONS[type] || 'bi-circle'),
                        'aria-hidden': 'true',
                    }),
                    label,
                    open &&
                        (description || level != null) &&
                        /*#__PURE__*/ React.createElement(
                            'span',
                            {
                                style: {
                                    position: 'absolute',
                                    top: 'calc(100% + 6px)',
                                    left: '50%',
                                    transform: 'translateX(-50%)',
                                    background: '#fff',
                                    border: '1px solid var(--color-border)',
                                    borderRadius: '6px',
                                    boxShadow: 'var(--shadow-dropdown)',
                                    padding: '0.5rem 0.75rem',
                                    minWidth: '200px',
                                    zIndex: 300,
                                    fontSize: '0.78rem',
                                    color: 'var(--color-text-secondary)',
                                    whiteSpace: 'normal',
                                },
                            },
                            level != null &&
                                /*#__PURE__*/ React.createElement(
                                    'div',
                                    {
                                        style: {
                                            color: 'var(--color-text-primary)',
                                            marginBottom: '0.2rem',
                                        },
                                    },
                                    'Level ',
                                    level,
                                ),
                            description,
                        ),
                );
            }
            Object.assign(__ds_scope, { EntityChip });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/data/EntityChip.jsx', error: String((e && e.message) || e) });
    }

    // components/data/ProgressBar.jsx
    try {
        (() => {
            function ProgressBar({ value, max, segmented = false, segments = 10, color = 'var(--color-accent)' }) {
                const pct = Math.max(0, Math.min(100, (value / max) * 100));
                if (!segmented) {
                    return /*#__PURE__*/ React.createElement(
                        'div',
                        {
                            style: {
                                position: 'relative',
                                height: '0.5rem',
                                background: '#eee',
                                borderRadius: '4px',
                                overflow: 'hidden',
                            },
                        },
                        /*#__PURE__*/ React.createElement('div', {
                            style: {
                                height: '100%',
                                borderRadius: '4px',
                                width: pct + '%',
                                background: color,
                                transition: 'width 0.3s ease',
                            },
                        }),
                    );
                }
                const filled = Math.round((value / max) * segments);
                return /*#__PURE__*/ React.createElement(
                    'div',
                    {
                        style: {
                            display: 'flex',
                            gap: '2px',
                            height: '0.28rem',
                        },
                    },
                    Array.from({
                        length: segments,
                    }).map((_, i) =>
                        /*#__PURE__*/ React.createElement('div', {
                            key: i,
                            style: {
                                flex: '1 1 0',
                                minWidth: 0,
                                borderRadius: '1px',
                                background: i < filled ? color : 'rgba(0,0,0,0.14)',
                                transition: 'background 0.15s ease',
                            },
                        }),
                    ),
                );
            }
            Object.assign(__ds_scope, { ProgressBar });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/data/ProgressBar.jsx', error: String((e && e.message) || e) });
    }

    // components/data/ResourceChip.jsx
    try {
        (() => {
            const RES_COLORS = {
                Cr: ['var(--res-credits-bg)', 'var(--res-credits)'],
                Sup: ['var(--res-supply-bg)', 'var(--res-supply)'],
                Rg: ['var(--res-regolith-bg)', 'var(--res-regolith)'],
                Co: ['var(--res-compounds-bg)', 'var(--res-compounds)'],
                Or: ['var(--res-organics-bg)', 'var(--res-organics)'],
                Tr: ['var(--res-trust-bg)', 'var(--res-trust)'],
                Sol: ['var(--res-sol-bg)', 'var(--res-sol)'],
                NX: ['var(--res-nexus-debt-bg)', 'var(--res-nexus-debt)'],
            };
            function ResourceChip({ abbr, amount, tone, empty = false }) {
                const [bg, border] = RES_COLORS[abbr] || ['#fff', 'rgba(0,0,0,0.12)'];
                const isSol = abbr === 'Sol';
                const toneStyle =
                    tone === 'warning'
                        ? {
                              background: 'var(--color-warning-bg)',
                              borderColor: 'var(--color-warning)',
                              color: 'var(--color-warning-fg)',
                          }
                        : tone === 'danger'
                          ? {
                                background: 'var(--color-danger-bg)',
                                borderColor: 'var(--color-accent)',
                                color: 'var(--color-accent)',
                            }
                          : isSol
                            ? {
                                  background: 'transparent',
                                  borderColor: 'transparent',
                              }
                            : {
                                  background: bg,
                                  borderColor: border,
                              };
                return /*#__PURE__*/ React.createElement(
                    'span',
                    {
                        style: {
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '4px',
                            padding: '3px 10px 3px 8px',
                            borderRadius: 'var(--radius-round)',
                            fontSize: '0.7rem',
                            fontWeight: 700,
                            border: '1px solid',
                            color: '#333',
                            whiteSpace: 'nowrap',
                            opacity: empty ? 0.45 : 1,
                            borderStyle: empty ? 'dashed' : 'solid',
                            ...toneStyle,
                        },
                    },
                    /*#__PURE__*/ React.createElement(
                        'span',
                        {
                            style: {
                                fontSize: '0.7rem',
                                fontWeight: 700,
                                opacity: 0.65,
                                textTransform: 'uppercase',
                                letterSpacing: '0.03em',
                            },
                        },
                        abbr,
                    ),
                    /*#__PURE__*/ React.createElement(
                        'span',
                        {
                            style: {
                                fontVariantNumeric: 'tabular-nums',
                            },
                        },
                        amount,
                    ),
                );
            }
            Object.assign(__ds_scope, { ResourceChip });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/data/ResourceChip.jsx', error: String((e && e.message) || e) });
    }

    // components/data/ResourceBar.jsx
    try {
        (() => {
            function _extends() {
                return (
                    (_extends = Object.assign
                        ? Object.assign.bind()
                        : function (n) {
                              for (var e = 1; e < arguments.length; e++) {
                                  var t = arguments[e];
                                  for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]);
                              }
                              return n;
                          }),
                    _extends.apply(null, arguments)
                );
            }
            function ResourceBar({ sol, credits, supply, trust, resources = [] }) {
                const divider = /*#__PURE__*/ React.createElement('span', {
                    style: {
                        display: 'inline-block',
                        width: '1px',
                        height: '22px',
                        background: '#ccc',
                        borderRadius: '1px',
                        margin: '0 2px',
                        alignSelf: 'center',
                    },
                });
                return /*#__PURE__*/ React.createElement(
                    'div',
                    {
                        style: {
                            display: 'flex',
                            flexWrap: 'wrap',
                            gap: '0.5rem',
                            justifyContent: 'center',
                            alignItems: 'center',
                            padding: '5px 12px',
                            background: 'var(--color-bg)',
                            borderBottom: '1px solid var(--color-border)',
                            boxShadow: '0 1px 3px rgba(0,0,0,0.04)',
                            fontFamily: 'var(--font-body)',
                        },
                    },
                    sol != null &&
                        /*#__PURE__*/ React.createElement(__ds_scope.ResourceChip, {
                            abbr: 'Sol',
                            amount: sol,
                        }),
                    divider,
                    credits != null &&
                        /*#__PURE__*/ React.createElement(__ds_scope.ResourceChip, {
                            abbr: 'Cr',
                            amount: credits,
                        }),
                    supply != null &&
                        /*#__PURE__*/ React.createElement(__ds_scope.ResourceChip, {
                            abbr: 'Sup',
                            amount: supply,
                        }),
                    trust != null &&
                        /*#__PURE__*/ React.createElement(__ds_scope.ResourceChip, {
                            abbr: 'Tr',
                            amount: trust,
                        }),
                    resources.length > 0 && divider,
                    resources.map((r) =>
                        /*#__PURE__*/ React.createElement(
                            __ds_scope.ResourceChip,
                            _extends(
                                {
                                    key: r.abbr,
                                },
                                r,
                            ),
                        ),
                    ),
                );
            }
            Object.assign(__ds_scope, { ResourceBar });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/data/ResourceBar.jsx', error: String((e && e.message) || e) });
    }

    // components/data/StatusBadge.jsx
    try {
        (() => {
            function StatusBadge({ tone = 'neutral', children }) {
                const map = {
                    success: {
                        background: 'var(--color-success)',
                        color: '#fff',
                    },
                    danger: {
                        background: 'var(--color-danger)',
                        color: '#fff',
                    },
                    warning: {
                        background: 'var(--color-warning-bg)',
                        color: 'var(--color-warning-fg)',
                        border: '1px solid var(--color-warning)',
                    },
                    neutral: {
                        background: 'var(--color-surface)',
                        color: 'var(--color-text-secondary)',
                        border: '1px solid var(--color-border)',
                    },
                };
                return /*#__PURE__*/ React.createElement(
                    'span',
                    {
                        style: {
                            display: 'inline-block',
                            padding: '0.15em 0.6em',
                            borderRadius: '0.3em',
                            fontSize: '0.78rem',
                            fontWeight: 600,
                            textTransform: 'uppercase',
                            letterSpacing: '0.03em',
                            fontFamily: 'var(--font-body)',
                            whiteSpace: 'nowrap',
                            ...map[tone],
                        },
                    },
                    children,
                );
            }
            Object.assign(__ds_scope, { StatusBadge });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/data/StatusBadge.jsx', error: String((e && e.message) || e) });
    }

    // components/data/Table.jsx
    try {
        (() => {
            function Table({ columns, rows }) {
                return /*#__PURE__*/ React.createElement(
                    'table',
                    {
                        style: {
                            width: '100%',
                            borderCollapse: 'collapse',
                            fontSize: '0.9rem',
                            fontFamily: 'var(--font-body)',
                        },
                    },
                    /*#__PURE__*/ React.createElement(
                        'thead',
                        null,
                        /*#__PURE__*/ React.createElement(
                            'tr',
                            null,
                            columns.map((c) =>
                                /*#__PURE__*/ React.createElement(
                                    'th',
                                    {
                                        key: c.key,
                                        style: {
                                            padding: '0.55rem 0.75rem',
                                            textAlign: c.numeric ? 'right' : 'left',
                                            verticalAlign: 'middle',
                                            fontWeight: 600,
                                            fontSize: '0.8rem',
                                            textTransform: 'uppercase',
                                            letterSpacing: '0.04em',
                                            color: 'var(--color-text-secondary)',
                                            borderBottom: '2px solid var(--color-border)',
                                        },
                                    },
                                    c.label,
                                ),
                            ),
                        ),
                    ),
                    /*#__PURE__*/ React.createElement(
                        'tbody',
                        null,
                        rows.map((row, i) =>
                            /*#__PURE__*/ React.createElement(
                                'tr',
                                {
                                    key: i,
                                    style: {
                                        borderBottom: i === rows.length - 1 ? 'none' : '1px solid var(--color-border)',
                                    },
                                },
                                columns.map((c) =>
                                    /*#__PURE__*/ React.createElement(
                                        'td',
                                        {
                                            key: c.key,
                                            style: {
                                                padding: '0.55rem 0.75rem',
                                                textAlign: c.numeric ? 'right' : 'left',
                                                verticalAlign: 'middle',
                                                fontVariantNumeric: c.numeric ? 'tabular-nums' : undefined,
                                                color: 'var(--color-text-primary)',
                                            },
                                        },
                                        row[c.key],
                                    ),
                                ),
                            ),
                        ),
                    ),
                );
            }
            Object.assign(__ds_scope, { Table });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/data/Table.jsx', error: String((e && e.message) || e) });
    }

    // components/feedback/Dialog.jsx
    try {
        (() => {
            function Dialog({ open, onClose, title, children, footer, width = '500px' }) {
                React.useEffect(() => {
                    if (!open) return;
                    const onKey = (e) => {
                        if (e.key === 'Escape') onClose && onClose();
                    };
                    window.addEventListener('keydown', onKey);
                    return () => window.removeEventListener('keydown', onKey);
                }, [open, onClose]);
                if (!open) return null;
                return /*#__PURE__*/ React.createElement(
                    'div',
                    {
                        onClick: onClose,
                        style: {
                            position: 'fixed',
                            inset: 0,
                            background: 'var(--color-scrim)',
                            backdropFilter: 'blur(3px)',
                            WebkitBackdropFilter: 'blur(3px)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            zIndex: 1000,
                            animation: 'ds-scrim-in var(--duration-base) ease-out',
                        },
                    },
                    /*#__PURE__*/ React.createElement(
                        'div',
                        {
                            onClick: (e) => e.stopPropagation(),
                            style: {
                                width: `min(90vw, ${width})`,
                                maxWidth: `min(90vw, ${width})`,
                                maxHeight: '85vh',
                                overflow: 'hidden',
                                position: 'relative',
                                clipPath: 'polygon(0 0, calc(100% - 20px) 0, 100% 20px, 100% 100%, 0 100%)',
                                background:
                                    'linear-gradient(to right, var(--color-accent) 0, var(--color-accent) 3px, transparent 3px), #ffffff',
                                filter: 'drop-shadow(0 12px 36px rgba(14,14,46,0.2)) drop-shadow(0 2px 6px rgba(14,14,46,0.1))',
                                animation: 'ds-dialog-in var(--duration-base) ease-out',
                                display: 'flex',
                                flexDirection: 'column',
                            },
                        },
                        /*#__PURE__*/ React.createElement('div', {
                            style: {
                                position: 'absolute',
                                top: 0,
                                left: '3px',
                                right: '20px',
                                height: '1px',
                                background: 'rgba(0,0,0,0.06)',
                            },
                        }),
                        title &&
                            /*#__PURE__*/ React.createElement(
                                'header',
                                {
                                    style: {
                                        display: 'flex',
                                        alignItems: 'center',
                                        padding: '0.9rem 3rem 0.75rem 1.25rem',
                                        borderBottom: '1px solid rgba(0,0,0,0.07)',
                                        position: 'relative',
                                        flexShrink: 0,
                                    },
                                },
                                /*#__PURE__*/ React.createElement(
                                    'h3',
                                    {
                                        style: {
                                            margin: 0,
                                            fontSize: '0.88rem',
                                            fontWeight: 700,
                                            letterSpacing: '0.06em',
                                            textTransform: 'uppercase',
                                            color: 'var(--color-anthracite)',
                                            overflow: 'hidden',
                                            textOverflow: 'ellipsis',
                                            whiteSpace: 'nowrap',
                                            flex: '1 1 0',
                                            minWidth: 0,
                                        },
                                    },
                                    title,
                                ),
                                /*#__PURE__*/ React.createElement(
                                    'button',
                                    {
                                        onClick: onClose,
                                        'aria-label': 'Close',
                                        style: {
                                            position: 'absolute',
                                            top: '50%',
                                            right: '1.5rem',
                                            transform: 'translateY(-50%)',
                                            background: 'none',
                                            border: 'none',
                                            cursor: 'pointer',
                                            color: '#bbb',
                                            fontSize: '1rem',
                                            lineHeight: 1,
                                            padding: '0.1rem 0.25rem',
                                            transition: 'color 0.15s',
                                        },
                                        onMouseEnter: (e) => (e.target.style.color = 'var(--color-anthracite)'),
                                        onMouseLeave: (e) => (e.target.style.color = '#bbb'),
                                    },
                                    '\u2715',
                                ),
                            ),
                        /*#__PURE__*/ React.createElement(
                            'div',
                            {
                                style: {
                                    padding: '0.9rem 1rem 0.9rem 1.25rem',
                                    overflowY: 'auto',
                                    fontSize: '0.9rem',
                                    color: 'var(--color-text-primary)',
                                    lineHeight: 1.5,
                                },
                            },
                            children,
                        ),
                        footer &&
                            /*#__PURE__*/ React.createElement(
                                'footer',
                                {
                                    style: {
                                        borderTop: '1px solid rgba(0,0,0,0.07)',
                                        padding: '0.75rem 1rem 0.9rem 1.25rem',
                                        display: 'flex',
                                        justifyContent: 'flex-end',
                                        gap: '0.5rem',
                                        flexShrink: 0,
                                    },
                                },
                                footer,
                            ),
                    ),
                );
            }
            Object.assign(__ds_scope, { Dialog });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/feedback/Dialog.jsx', error: String((e && e.message) || e) });
    }

    // components/forms/Checkbox.jsx
    try {
        (() => {
            function Checkbox({ checked, onChange, label, disabled = false, id, name }) {
                return /*#__PURE__*/ React.createElement(
                    'label',
                    {
                        htmlFor: id,
                        style: {
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '0.55rem',
                            fontFamily: 'var(--font-body)',
                            fontSize: '0.85rem',
                            color: 'var(--color-text-primary)',
                            cursor: disabled ? 'not-allowed' : 'pointer',
                            opacity: disabled ? 0.45 : 1,
                            userSelect: 'none',
                        },
                    },
                    /*#__PURE__*/ React.createElement('input', {
                        type: 'checkbox',
                        id: id,
                        name: name,
                        checked: checked,
                        onChange: onChange,
                        disabled: disabled,
                        style: {
                            width: '16px',
                            height: '16px',
                            margin: 0,
                            accentColor: 'var(--color-accent)',
                            cursor: disabled ? 'not-allowed' : 'pointer',
                            flexShrink: 0,
                        },
                    }),
                    label,
                );
            }
            Object.assign(__ds_scope, { Checkbox });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/forms/Checkbox.jsx', error: String((e && e.message) || e) });
    }

    // components/forms/FormField.jsx
    try {
        (() => {
            function FormField({ label, htmlFor, error, hint, children }) {
                return /*#__PURE__*/ React.createElement(
                    'div',
                    {
                        style: {
                            display: 'flex',
                            flexDirection: 'column',
                            gap: '0.3rem',
                            fontFamily: 'var(--font-body)',
                        },
                    },
                    label &&
                        /*#__PURE__*/ React.createElement(
                            'label',
                            {
                                htmlFor: htmlFor,
                                style: {
                                    fontFamily: 'var(--font-body)',
                                    fontSize: '0.72rem',
                                    color: 'var(--color-text-secondary)',
                                    fontWeight: 600,
                                },
                            },
                            label,
                        ),
                    children,
                    hint &&
                        !error &&
                        /*#__PURE__*/ React.createElement(
                            'span',
                            {
                                style: {
                                    fontFamily: 'var(--font-body)',
                                    fontSize: '0.72rem',
                                    color: 'var(--color-text-secondary)',
                                },
                            },
                            hint,
                        ),
                    error &&
                        /*#__PURE__*/ React.createElement(
                            'span',
                            {
                                style: {
                                    fontFamily: 'var(--font-body)',
                                    fontSize: '0.75rem',
                                    color: 'var(--color-accent)',
                                    minHeight: '1em',
                                },
                            },
                            error,
                        ),
                );
            }
            Object.assign(__ds_scope, { FormField });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/forms/FormField.jsx', error: String((e && e.message) || e) });
    }

    // components/forms/Input.jsx
    try {
        (() => {
            const FIELD_BASE = {
                width: '100%',
                fontFamily: 'var(--font-body)',
                fontSize: '0.85rem',
                padding: '0.45rem 0.65rem',
                border: '1px solid var(--color-border-strong)',
                borderRadius: 'var(--radius-sm)',
                background: 'var(--color-input-bg)',
                color: 'var(--color-text-primary)',
                boxSizing: 'border-box',
                transition: 'border-color 0.15s, box-shadow 0.15s',
                outline: 'none',
            };
            function Input({ type = 'text', value, onChange, placeholder, disabled = false, id, name, style }) {
                const [focus, setFocus] = React.useState(false);
                return /*#__PURE__*/ React.createElement('input', {
                    type: type,
                    id: id,
                    name: name,
                    value: value,
                    onChange: onChange,
                    placeholder: placeholder,
                    disabled: disabled,
                    onFocus: () => setFocus(true),
                    onBlur: () => setFocus(false),
                    style: {
                        ...FIELD_BASE,
                        opacity: disabled ? 0.45 : 1,
                        cursor: disabled ? 'not-allowed' : 'text',
                        borderColor: focus ? 'var(--color-accent)' : 'var(--color-border-strong)',
                        boxShadow: focus ? '0 0 0 3px var(--color-accent-tint)' : 'none',
                        ...style,
                    },
                });
            }
            Object.assign(__ds_scope, { Input });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/forms/Input.jsx', error: String((e && e.message) || e) });
    }

    // components/forms/RangeSlider.jsx
    try {
        (() => {
            function RangeSlider({ value, min = 0, max = 100, step = 1, onChange, disabled = false, id, name }) {
                return /*#__PURE__*/ React.createElement('input', {
                    type: 'range',
                    id: id,
                    name: name,
                    value: value,
                    min: min,
                    max: max,
                    step: step,
                    onChange: onChange,
                    disabled: disabled,
                    style: {
                        width: '100%',
                        accentColor: 'var(--color-accent)',
                        cursor: disabled ? 'not-allowed' : 'pointer',
                        opacity: disabled ? 0.45 : 1,
                    },
                });
            }
            Object.assign(__ds_scope, { RangeSlider });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/forms/RangeSlider.jsx', error: String((e && e.message) || e) });
    }

    // components/forms/Select.jsx
    try {
        (() => {
            function Select({ value, onChange, options = [], disabled = false, id, name, style }) {
                const [focus, setFocus] = React.useState(false);
                return /*#__PURE__*/ React.createElement(
                    'div',
                    {
                        style: {
                            position: 'relative',
                            width: '100%',
                        },
                    },
                    /*#__PURE__*/ React.createElement(
                        'select',
                        {
                            value: value,
                            onChange: onChange,
                            disabled: disabled,
                            id: id,
                            name: name,
                            onFocus: () => setFocus(true),
                            onBlur: () => setFocus(false),
                            style: {
                                width: '100%',
                                appearance: 'none',
                                WebkitAppearance: 'none',
                                fontFamily: 'var(--font-body)',
                                fontSize: '0.85rem',
                                padding: '0.45rem 2rem 0.45rem 0.65rem',
                                border: '1px solid var(--color-border-strong)',
                                borderRadius: 'var(--radius-sm)',
                                background: 'var(--color-input-bg)',
                                color: 'var(--color-text-primary)',
                                boxSizing: 'border-box',
                                cursor: disabled ? 'not-allowed' : 'pointer',
                                opacity: disabled ? 0.45 : 1,
                                outline: 'none',
                                borderColor: focus ? 'var(--color-accent)' : 'var(--color-border-strong)',
                                boxShadow: focus ? '0 0 0 3px var(--color-accent-tint)' : 'none',
                                ...style,
                            },
                        },
                        options.map((o) =>
                            /*#__PURE__*/ React.createElement(
                                'option',
                                {
                                    key: o.value,
                                    value: o.value,
                                },
                                o.label,
                            ),
                        ),
                    ),
                    /*#__PURE__*/ React.createElement('i', {
                        className: 'bi bi-chevron-down',
                        style: {
                            position: 'absolute',
                            right: '0.65rem',
                            top: '50%',
                            transform: 'translateY(-50%)',
                            fontSize: '0.7rem',
                            color: 'var(--color-text-secondary)',
                            pointerEvents: 'none',
                        },
                    }),
                );
            }
            Object.assign(__ds_scope, { Select });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/forms/Select.jsx', error: String((e && e.message) || e) });
    }

    // components/forms/Switch.jsx
    try {
        (() => {
            function Switch({ checked, onChange, label, disabled = false, id, name }) {
                return /*#__PURE__*/ React.createElement(
                    'label',
                    {
                        htmlFor: id,
                        style: {
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '0.55rem',
                            fontFamily: 'var(--font-body)',
                            fontSize: '0.85rem',
                            color: 'var(--color-text-primary)',
                            cursor: disabled ? 'not-allowed' : 'pointer',
                            opacity: disabled ? 0.45 : 1,
                            userSelect: 'none',
                        },
                    },
                    /*#__PURE__*/ React.createElement(
                        'span',
                        {
                            style: {
                                position: 'relative',
                                width: '34px',
                                height: '20px',
                                flexShrink: 0,
                                borderRadius: 'var(--radius-round)',
                                background: checked ? 'var(--color-accent)' : 'var(--color-border-strong)',
                                transition: 'background 0.15s',
                            },
                        },
                        /*#__PURE__*/ React.createElement('input', {
                            type: 'checkbox',
                            role: 'switch',
                            id: id,
                            name: name,
                            checked: checked,
                            onChange: onChange,
                            disabled: disabled,
                            style: {
                                position: 'absolute',
                                inset: 0,
                                opacity: 0,
                                margin: 0,
                                cursor: disabled ? 'not-allowed' : 'pointer',
                            },
                        }),
                        /*#__PURE__*/ React.createElement('span', {
                            style: {
                                position: 'absolute',
                                top: '2px',
                                left: checked ? '16px' : '2px',
                                width: '16px',
                                height: '16px',
                                borderRadius: '50%',
                                background: '#fff',
                                boxShadow: '0 1px 2px rgba(0,0,0,0.25)',
                                transition: 'left 0.15s',
                            },
                        }),
                    ),
                    label,
                );
            }
            Object.assign(__ds_scope, { Switch });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/forms/Switch.jsx', error: String((e && e.message) || e) });
    }

    // components/navigation/Navbar.jsx
    try {
        (() => {
            function Navbar({ items = [], active, rightSlot, onSelect }) {
                return /*#__PURE__*/ React.createElement(
                    'nav',
                    {
                        style: {
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            height: '60px',
                            padding: '0 1rem',
                            background: 'var(--color-navbar-bg)',
                            borderBottom: '1px solid var(--color-navbar-border)',
                            fontFamily: 'var(--font-body)',
                        },
                    },
                    /*#__PURE__*/ React.createElement(
                        'a',
                        {
                            href: '#',
                            style: {
                                fontFamily: 'var(--font-display)',
                                fontWeight: 400,
                                letterSpacing: '0.45em',
                                textTransform: 'uppercase',
                                color: 'var(--color-text-primary)',
                                fontSize: '0.9rem',
                                textDecoration: 'none',
                            },
                        },
                        'Nouron',
                    ),
                    /*#__PURE__*/ React.createElement(
                        'ul',
                        {
                            style: {
                                display: 'flex',
                                flex: 1,
                                justifyContent: 'center',
                                gap: '1rem',
                                listStyle: 'none',
                                margin: 0,
                                padding: 0,
                            },
                        },
                        items.map((it) =>
                            /*#__PURE__*/ React.createElement(
                                'li',
                                {
                                    key: it.key,
                                },
                                /*#__PURE__*/ React.createElement(
                                    'a',
                                    {
                                        href: '#',
                                        onClick: (e) => {
                                            e.preventDefault();
                                            !it.locked && onSelect && onSelect(it.key);
                                        },
                                        style: {
                                            display: 'inline-flex',
                                            alignItems: 'center',
                                            gap: '0.35rem',
                                            fontSize: '0.85rem',
                                            padding: '10px',
                                            textDecoration: 'none',
                                            whiteSpace: 'nowrap',
                                            color: it.locked
                                                ? 'var(--color-text-secondary)'
                                                : active === it.key
                                                  ? 'var(--color-accent)'
                                                  : '#4a4a58',
                                            fontWeight: active === it.key ? 600 : 400,
                                            opacity: it.locked ? 0.45 : 1,
                                            background: active === it.key ? 'var(--color-accent-tint)' : 'transparent',
                                            borderRadius: '6px',
                                            cursor: it.locked ? 'default' : 'pointer',
                                        },
                                    },
                                    it.icon &&
                                        /*#__PURE__*/ React.createElement('i', {
                                            className: 'bi ' + it.icon,
                                            'aria-hidden': 'true',
                                        }),
                                    it.label,
                                ),
                            ),
                        ),
                    ),
                    /*#__PURE__*/ React.createElement(
                        'div',
                        {
                            style: {
                                display: 'flex',
                                alignItems: 'center',
                                gap: '0.75rem',
                            },
                        },
                        rightSlot,
                    ),
                );
            }
            Object.assign(__ds_scope, { Navbar });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/navigation/Navbar.jsx', error: String((e && e.message) || e) });
    }

    // components/navigation/SubnavTabs.jsx
    try {
        (() => {
            function SubnavTabs({ tabs, active, onSelect }) {
                return /*#__PURE__*/ React.createElement(
                    'div',
                    {
                        style: {
                            display: 'flex',
                            gap: '1.5rem',
                            borderBottom: '1px solid var(--color-border)',
                            background: 'var(--color-bg)',
                            padding: '0 1rem',
                            fontFamily: 'var(--font-body)',
                        },
                    },
                    tabs.map((t) =>
                        /*#__PURE__*/ React.createElement(
                            'a',
                            {
                                key: t.key,
                                href: '#',
                                onClick: (e) => {
                                    e.preventDefault();
                                    onSelect && onSelect(t.key);
                                },
                                style: {
                                    padding: '0.6rem 0',
                                    fontSize: '0.875rem',
                                    textDecoration: 'none',
                                    color: active === t.key ? 'var(--color-accent)' : 'var(--color-text-secondary)',
                                    borderBottom:
                                        active === t.key ? '2px solid var(--color-accent)' : '2px solid transparent',
                                },
                            },
                            t.label,
                        ),
                    ),
                );
            }
            Object.assign(__ds_scope, { SubnavTabs });
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'components/navigation/SubnavTabs.jsx', error: String((e && e.message) || e) });
    }

    // ui_kits/colony/ColonyScreen.jsx
    try {
        (() => {
            function _extends() {
                return (
                    (_extends = Object.assign
                        ? Object.assign.bind()
                        : function (n) {
                              for (var e = 1; e < arguments.length; e++) {
                                  var t = arguments[e];
                                  for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]);
                              }
                              return n;
                          }),
                    _extends.apply(null, arguments)
                );
            }
            const { Navbar, ResourceBar, SubnavTabs, Card, Button, Dialog, EntityChip, ProgressBar } =
                window.NouronDesignSystem_019dc5;
            const NAV_ITEMS = [
                {
                    key: 'colony',
                    label: 'Kolonie',
                    icon: 'bi-hexagon',
                },
                {
                    key: 'command_center',
                    label: 'Command Center',
                    icon: 'bi-diagram-2',
                },
                {
                    key: 'advisors',
                    label: 'Berater',
                    icon: 'bi-people',
                },
                {
                    key: 'techtree',
                    label: 'Techtree',
                    icon: 'bi-diagram-3',
                },
                {
                    key: 'cantina',
                    label: 'Cantina',
                    icon: 'bi-cup-hot',
                },
                {
                    key: 'hangar',
                    label: 'Hangar',
                    icon: 'bi-rocket',
                    locked: true,
                },
                {
                    key: 'log',
                    label: 'Protokoll',
                    icon: 'bi-journal-text',
                },
            ];
            const TILES = [
                {
                    id: 'cc',
                    x: 260,
                    y: 120,
                    fill: '#7ec87e',
                    stroke: '#2e7d32',
                    label: 'Command Center',
                    type: 'Command Center',
                    level: 2,
                },
                {
                    id: 'harv',
                    x: 340,
                    y: 165,
                    fill: '#7fb5dc',
                    stroke: '#5090c0',
                    label: 'Harvester',
                    type: 'Regolith deposit',
                    level: 1,
                },
                {
                    id: 'hab',
                    x: 180,
                    y: 165,
                    fill: '#c8cdd6',
                    stroke: '#a0a8b4',
                    label: 'Residential Habitat',
                    type: 'Buildable',
                    level: 1,
                },
                {
                    id: 'haz',
                    x: 260,
                    y: 210,
                    fill: '#e8b87a',
                    stroke: '#c08040',
                    label: 'Hazard zone',
                    type: 'Hazard',
                    level: null,
                },
                {
                    id: 'fog',
                    x: 420,
                    y: 120,
                    fill: '#9aa4b8',
                    stroke: '#6f7a90',
                    label: 'Unexplored',
                    type: 'Explore target',
                    level: null,
                },
            ];
            function Hex({ x, y, fill, stroke, selected, onClick }) {
                const r = 34;
                const pts = Array.from({
                    length: 6,
                })
                    .map((_, i) => {
                        const a = (Math.PI / 180) * (60 * i - 30);
                        return `${x + r * Math.cos(a)},${y + r * Math.sin(a)}`;
                    })
                    .join(' ');
                return /*#__PURE__*/ React.createElement('polygon', {
                    points: pts,
                    fill: fill,
                    stroke: selected ? 'var(--color-accent)' : stroke,
                    strokeWidth: selected ? 3 : 1.5,
                    onClick: onClick,
                    style: {
                        cursor: 'pointer',
                    },
                });
            }
            function ColonyScreen({ onExit }) {
                const [active, setActive] = React.useState('colony');
                const [tab, setTab] = React.useState('overview');
                const [selected, setSelected] = React.useState(TILES[0]);
                const [confirmEnd, setConfirmEnd] = React.useState(false);
                const pendingAp = 2;
                return /*#__PURE__*/ React.createElement(
                    'div',
                    {
                        style: {
                            fontFamily: 'var(--font-body)',
                            color: 'var(--color-text-primary)',
                            background: '#fff',
                        },
                    },
                    /*#__PURE__*/ React.createElement(Navbar, {
                        items: NAV_ITEMS,
                        active: active,
                        onSelect: (k) => {
                            if (k === 'colony') setActive(k);
                            else setActive(k);
                        },
                        rightSlot: /*#__PURE__*/ React.createElement(
                            React.Fragment,
                            null,
                            /*#__PURE__*/ React.createElement(
                                Button,
                                {
                                    variant: 'ghost',
                                    onClick: onExit,
                                },
                                'Exit',
                            ),
                            /*#__PURE__*/ React.createElement(
                                Button,
                                {
                                    variant: 'primary',
                                    onClick: () => (pendingAp > 0 ? setConfirmEnd(true) : null),
                                },
                                'End Sol',
                            ),
                        ),
                    }),
                    /*#__PURE__*/ React.createElement(ResourceBar, {
                        sol: 42,
                        credits: 3120,
                        supply: 18,
                        trust: 64,
                        resources: [
                            {
                                abbr: 'Rg',
                                amount: 210,
                            },
                            {
                                abbr: 'Co',
                                amount: 40,
                            },
                            {
                                abbr: 'Or',
                                amount: 12,
                            },
                        ],
                    }),
                    /*#__PURE__*/ React.createElement(SubnavTabs, {
                        tabs: [
                            {
                                key: 'overview',
                                label: 'Overview',
                            },
                            {
                                key: 'legend',
                                label: 'Legend',
                            },
                        ],
                        active: tab,
                        onSelect: setTab,
                    }),
                    /*#__PURE__*/ React.createElement(
                        'div',
                        {
                            style: {
                                display: 'grid',
                                gridTemplateColumns: '1fr 320px',
                                minHeight: '420px',
                            },
                        },
                        /*#__PURE__*/ React.createElement(
                            'div',
                            {
                                style: {
                                    background: '#fafafa',
                                    borderRight: '1px solid var(--color-border)',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    padding: '1.5rem',
                                },
                            },
                            /*#__PURE__*/ React.createElement(
                                'svg',
                                {
                                    width: '480',
                                    height: '300',
                                    viewBox: '0 0 480 300',
                                },
                                TILES.map((t) =>
                                    /*#__PURE__*/ React.createElement(
                                        Hex,
                                        _extends(
                                            {
                                                key: t.id,
                                            },
                                            t,
                                            {
                                                selected: selected.id === t.id,
                                                onClick: () => setSelected(t),
                                            },
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        /*#__PURE__*/ React.createElement(
                            'div',
                            {
                                style: {
                                    padding: '1.25rem',
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: '1rem',
                                },
                            },
                            /*#__PURE__*/ React.createElement(
                                'div',
                                {
                                    style: {
                                        paddingBottom: '0.6rem',
                                        borderBottom: '2px solid var(--color-accent)',
                                    },
                                },
                                /*#__PURE__*/ React.createElement(
                                    'h3',
                                    {
                                        style: {
                                            margin: 0,
                                            fontSize: '1rem',
                                            fontWeight: 700,
                                            textTransform: 'uppercase',
                                            letterSpacing: '0.06em',
                                        },
                                    },
                                    selected.label,
                                ),
                            ),
                            /*#__PURE__*/ React.createElement(
                                'p',
                                {
                                    style: {
                                        margin: 0,
                                        fontSize: '0.85rem',
                                        color: 'var(--color-text-secondary)',
                                    },
                                },
                                selected.type,
                                selected.level != null && ` — Level ${selected.level}`,
                            ),
                            selected.id === 'cc' &&
                                /*#__PURE__*/ React.createElement(EntityChip, {
                                    type: 'building',
                                    label: 'Command Center',
                                    level: 2,
                                    description: 'Coordinates colony administration and unlocks new build tiers.',
                                }),
                            selected.level != null &&
                                /*#__PURE__*/ React.createElement(
                                    'div',
                                    null,
                                    /*#__PURE__*/ React.createElement(
                                        'div',
                                        {
                                            style: {
                                                fontSize: '0.72rem',
                                                color: '#888',
                                                marginBottom: '0.25rem',
                                                display: 'flex',
                                                justifyContent: 'space-between',
                                            },
                                        },
                                        /*#__PURE__*/ React.createElement('span', null, 'Condition'),
                                        /*#__PURE__*/ React.createElement('span', null, '80%'),
                                    ),
                                    /*#__PURE__*/ React.createElement(ProgressBar, {
                                        value: 80,
                                        max: 100,
                                        color: '#2196f3',
                                    }),
                                ),
                            /*#__PURE__*/ React.createElement(
                                'div',
                                {
                                    style: {
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: '0.5rem',
                                        marginTop: '0.5rem',
                                    },
                                },
                                /*#__PURE__*/ React.createElement(
                                    Button,
                                    {
                                        variant: 'primary',
                                        apCost: 1,
                                        apType: 'build',
                                    },
                                    'Repair',
                                ),
                                /*#__PURE__*/ React.createElement(
                                    Button,
                                    {
                                        variant: 'secondary',
                                        apCost: 2,
                                        apType: 'nav',
                                    },
                                    'Explore adjacent',
                                ),
                            ),
                        ),
                    ),
                    /*#__PURE__*/ React.createElement(
                        Dialog,
                        {
                            open: confirmEnd,
                            onClose: () => setConfirmEnd(false),
                            title: 'End Sol?',
                            footer: /*#__PURE__*/ React.createElement(
                                React.Fragment,
                                null,
                                /*#__PURE__*/ React.createElement(
                                    Button,
                                    {
                                        variant: 'ghost',
                                        onClick: () => setConfirmEnd(false),
                                    },
                                    'Keep playing',
                                ),
                                /*#__PURE__*/ React.createElement(
                                    Button,
                                    {
                                        variant: 'primary',
                                        onClick: () => setConfirmEnd(false),
                                    },
                                    'End Sol anyway',
                                ),
                            ),
                        },
                        /*#__PURE__*/ React.createElement(
                            'p',
                            {
                                style: {
                                    margin: 0,
                                    fontSize: '0.9rem',
                                    color: 'var(--color-text-secondary)',
                                    lineHeight: 1.5,
                                },
                            },
                            'You still have ',
                            pendingAp,
                            ' AP unspent this Sol. Unused AP expires at Sol end.',
                        ),
                    ),
                );
            }
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'ui_kits/colony/ColonyScreen.jsx', error: String((e && e.message) || e) });
    }

    // ui_kits/colony/LobbyScreen.jsx
    try {
        (() => {
            const { Card, Button, StatusBadge, Table } = window.NouronDesignSystem_019dc5;
            const ACTIVE = {
                name: 'Springfield',
                sol: 42,
                limit: 100,
            };
            function LobbyScreen({ onEnterColony }) {
                const HIGHSCORE = [
                    {
                        m: 'Run #12',
                        s: /*#__PURE__*/ React.createElement(
                            StatusBadge,
                            {
                                tone: 'success',
                            },
                            'Completed',
                        ),
                        sol: '100/100',
                        sc: '48,200',
                    },
                    {
                        m: 'Run #11',
                        s: /*#__PURE__*/ React.createElement(
                            StatusBadge,
                            {
                                tone: 'danger',
                            },
                            'Failed',
                        ),
                        sol: '37/100',
                        sc: '12,050',
                    },
                    {
                        m: 'Run #10',
                        s: /*#__PURE__*/ React.createElement(
                            StatusBadge,
                            {
                                tone: 'success',
                            },
                            'Completed',
                        ),
                        sol: '100/100',
                        sc: '44,900',
                    },
                ];
                return /*#__PURE__*/ React.createElement(
                    'div',
                    {
                        style: {
                            maxWidth: '56rem',
                            margin: '0 auto',
                            padding: '2rem 1.5rem 3rem',
                            fontFamily: 'var(--font-body)',
                            color: 'var(--color-text-primary)',
                        },
                    },
                    /*#__PURE__*/ React.createElement(
                        'div',
                        {
                            style: {
                                marginBottom: '1.75rem',
                            },
                        },
                        /*#__PURE__*/ React.createElement(
                            'h1',
                            {
                                style: {
                                    fontFamily: 'var(--font-display)',
                                    fontWeight: 400,
                                    textTransform: 'uppercase',
                                    letterSpacing: '0.45em',
                                    fontSize: '2rem',
                                    margin: '0 0 0.25rem',
                                },
                            },
                            'Missions',
                        ),
                        /*#__PURE__*/ React.createElement(
                            'p',
                            {
                                style: {
                                    color: 'var(--color-text-secondary)',
                                    margin: 0,
                                },
                            },
                            'Every colony is a fresh attempt against decay, scarcity, and the silence of Zone Ypsilon-7.',
                        ),
                    ),
                    /*#__PURE__*/ React.createElement(
                        'h2',
                        {
                            style: {
                                fontSize: '1.1rem',
                                fontWeight: 600,
                                margin: '1.75rem 0 0.25rem',
                                paddingBottom: '0.35rem',
                                borderBottom: '1px solid var(--color-border)',
                            },
                        },
                        'Active Runs',
                    ),
                    /*#__PURE__*/ React.createElement(
                        'div',
                        {
                            style: {
                                display: 'grid',
                                gridTemplateColumns: 'repeat(auto-fill,minmax(18rem,1fr))',
                                gap: '1rem',
                                marginTop: '0.75rem',
                            },
                        },
                        /*#__PURE__*/ React.createElement(
                            Card,
                            {
                                title: ACTIVE.name,
                                footer: /*#__PURE__*/ React.createElement(
                                    React.Fragment,
                                    null,
                                    /*#__PURE__*/ React.createElement(
                                        Button,
                                        {
                                            variant: 'primary',
                                            onClick: onEnterColony,
                                        },
                                        'Continue',
                                    ),
                                    /*#__PURE__*/ React.createElement(
                                        Button,
                                        {
                                            variant: 'secondary',
                                        },
                                        'Abandon',
                                    ),
                                ),
                            },
                            /*#__PURE__*/ React.createElement(
                                'p',
                                {
                                    style: {
                                        fontSize: '0.85rem',
                                        color: 'var(--color-text-secondary)',
                                        margin: '0 0 0.5rem',
                                    },
                                },
                                'Sol ',
                                ACTIVE.sol,
                                ' / ',
                                ACTIVE.limit,
                            ),
                            /*#__PURE__*/ React.createElement(
                                'div',
                                {
                                    style: {
                                        position: 'relative',
                                        height: '0.5rem',
                                        background: '#eee',
                                        borderRadius: '4px',
                                        overflow: 'hidden',
                                    },
                                },
                                /*#__PURE__*/ React.createElement('div', {
                                    style: {
                                        height: '100%',
                                        width: (ACTIVE.sol / ACTIVE.limit) * 100 + '%',
                                        background: 'var(--color-accent)',
                                        borderRadius: '4px',
                                    },
                                }),
                            ),
                        ),
                    ),
                    /*#__PURE__*/ React.createElement(
                        'h2',
                        {
                            style: {
                                fontSize: '1.1rem',
                                fontWeight: 600,
                                margin: '1.75rem 0 0.25rem',
                                paddingBottom: '0.35rem',
                                borderBottom: '1px solid var(--color-border)',
                            },
                        },
                        'Highscore',
                    ),
                    /*#__PURE__*/ React.createElement(
                        'div',
                        {
                            style: {
                                marginTop: '0.75rem',
                            },
                        },
                        /*#__PURE__*/ React.createElement(Table, {
                            columns: [
                                {
                                    key: 'm',
                                    label: 'Mission',
                                },
                                {
                                    key: 's',
                                    label: 'Status',
                                },
                                {
                                    key: 'sol',
                                    label: 'Sol',
                                },
                                {
                                    key: 'sc',
                                    label: 'Score',
                                    numeric: true,
                                },
                            ],
                            rows: HIGHSCORE,
                        }),
                    ),
                );
            }
        })();
    } catch (e) {
        __ds_ns.__errors.push({ path: 'ui_kits/colony/LobbyScreen.jsx', error: String((e && e.message) || e) });
    }

    __ds_ns.Button = __ds_scope.Button;

    __ds_ns.Card = __ds_scope.Card;

    __ds_ns.APChip = __ds_scope.APChip;

    __ds_ns.EntityChip = __ds_scope.EntityChip;

    __ds_ns.ProgressBar = __ds_scope.ProgressBar;

    __ds_ns.ResourceBar = __ds_scope.ResourceBar;

    __ds_ns.ResourceChip = __ds_scope.ResourceChip;

    __ds_ns.StatusBadge = __ds_scope.StatusBadge;

    __ds_ns.Table = __ds_scope.Table;

    __ds_ns.Dialog = __ds_scope.Dialog;

    __ds_ns.Checkbox = __ds_scope.Checkbox;

    __ds_ns.FormField = __ds_scope.FormField;

    __ds_ns.Input = __ds_scope.Input;

    __ds_ns.RangeSlider = __ds_scope.RangeSlider;

    __ds_ns.Select = __ds_scope.Select;

    __ds_ns.Switch = __ds_scope.Switch;

    __ds_ns.Navbar = __ds_scope.Navbar;

    __ds_ns.SubnavTabs = __ds_scope.SubnavTabs;
})();
