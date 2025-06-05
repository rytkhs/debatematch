export default {
    testEnvironment: 'jsdom',
    setupFilesAfterEnv: ['<rootDir>/tests/js/setup.js'],
    testMatch: [
        '<rootDir>/tests/js/**/*.test.js'
    ],
    collectCoverageFrom: [
        'resources/js/**/*.js',
        '!resources/js/bootstrap.js',
        '!resources/js/app.js',
        '!**/node_modules/**'
    ],
    coverageDirectory: 'coverage',
    coverageReporters: ['text', 'lcov', 'html'],
    moduleNameMapper: {
        '^@/(.*)$': '<rootDir>/resources/js/$1'
    },
    transform: {}
};
