const plugin = require('tailwindcss/plugin')

const base = plugin(function ({addBase, theme}) {
    addBase({
        'h1': {
            fontSize: theme('fontSize.4xl'),
            fontWeight: theme('fontWeight.bold'),
            lineHeight: theme('lineHeight.9'),
            color: theme('colors.gray.900'),
            marginBottom: theme('spacing.6'),
            '@media (prefers-color-scheme: dark)': {
                color: theme('colors.gray.50'),
            }
        },
        'h2': {
            fontSize: theme('fontSize.3xl'),
            fontWeight: theme('fontWeight.bold'),
            color: theme('colors.gray.900'),
            '@media (prefers-color-scheme: dark)': {
                color: theme('colors.gray.50'),
            }
        },
        'h3': {
            fontSize: theme('fontSize.lg'),
            fontWeight: theme('fontWeight.medium'),
            color: theme('colors.gray.900'),
            '@media (prefers-color-scheme: dark)': {
                color: theme('colors.gray.50'),
            }
        },
        '[x-cloak]': {
            display: 'none',
        },
    })
})

module.exports = base
