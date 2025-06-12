export default {
    testEnvironment: 'jsdom',
    setupFilesAfterEnv: ['<rootDir>/tests/js/setup.js'],
    testMatch: ['<rootDir>/tests/js/**/*.test.js'],
    collectCoverageFrom: [
        'resources/js/**/*.js',
        '!resources/js/bootstrap.js',
        '!resources/js/app.js',
        '!**/node_modules/**',
    ],
    coverageDirectory: 'coverage',
    coverageReporters: ['text', 'lcov', 'html'],
    coverageThreshold: {
        global: {
            branches: 60,
            functions: 65,
            lines: 65,
            statements: 65,
        },
    },
    moduleNameMapper: {
        '^@/(.*)$': '<rootDir>/resources/js/$1',
    },
    testTimeout: 10000,
    clearMocks: true,
    restoreMocks: true,
    transform: {
        '^.+\\.js$': 'babel-jest',
    },
    maxWorkers: '50%',
};
