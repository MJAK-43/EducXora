export default {
    plugins: {
        'postcss-preset-env': {
            stage: 3,
            autoprefixer: {
                flexbox: 'no-2009',
            },
        },
    },
};
