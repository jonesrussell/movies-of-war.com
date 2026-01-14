import { describe, expect, it } from 'vitest';

import {
    cn,
    filterUndefinedProps,
    filterUndefinedReactive,
    toUrl,
} from '../utils';

describe('cn', () => {
    it('merges class names correctly', () => {
        expect(cn('foo', 'bar')).toBe('foo bar');
    });

    it('handles conditional classes', () => {
        expect(cn('foo', false && 'bar', 'baz')).toBe('foo baz');
    });

    it('handles undefined and null values', () => {
        expect(cn('foo', undefined, null, 'bar')).toBe('foo bar');
    });

    it('merges tailwind classes correctly', () => {
        expect(cn('px-2 py-1', 'px-4')).toBe('py-1 px-4');
    });

    it('handles arrays of classes', () => {
        expect(cn(['foo', 'bar'], 'baz')).toBe('foo bar baz');
    });

    it('handles object syntax', () => {
        expect(cn({ foo: true, bar: false, baz: true })).toBe('foo baz');
    });

    it('returns empty string for no inputs', () => {
        expect(cn()).toBe('');
    });
});

describe('toUrl', () => {
    it('returns string href as-is', () => {
        expect(toUrl('/movies')).toBe('/movies');
    });

    it('extracts url from route object', () => {
        expect(toUrl({ url: '/movies/1', method: 'get' })).toBe('/movies/1');
    });

    it('handles undefined url in object', () => {
        // @ts-expect-error - Testing edge case with undefined url
        expect(toUrl({ url: undefined, method: 'get' })).toBeUndefined();
    });

    it('handles complex URLs', () => {
        expect(toUrl('/movies?page=2&sort=title')).toBe(
            '/movies?page=2&sort=title',
        );
    });
});

describe('filterUndefinedProps', () => {
    it('removes undefined properties', () => {
        const input = { a: 1, b: undefined, c: 'test' };
        expect(filterUndefinedProps(input)).toEqual({ a: 1, c: 'test' });
    });

    it('keeps null values', () => {
        const input = { a: 1, b: null, c: 'test' };
        expect(filterUndefinedProps(input)).toEqual({
            a: 1,
            b: null,
            c: 'test',
        });
    });

    it('returns empty object for all undefined', () => {
        const input = { a: undefined, b: undefined };
        expect(filterUndefinedProps(input)).toEqual({});
    });

    it('handles empty object', () => {
        expect(filterUndefinedProps({})).toEqual({});
    });

    it('keeps falsy values that are not undefined', () => {
        const input = { a: 0, b: '', c: false, d: undefined };
        expect(filterUndefinedProps(input)).toEqual({ a: 0, b: '', c: false });
    });
});

describe('filterUndefinedReactive', () => {
    it('removes undefined properties', () => {
        const input = { a: 1, b: undefined, c: 'test' };
        expect(filterUndefinedReactive(input)).toEqual({ a: 1, c: 'test' });
    });

    it('keeps null values', () => {
        const input = { a: 1, b: null, c: 'test' };
        expect(filterUndefinedReactive(input)).toEqual({
            a: 1,
            b: null,
            c: 'test',
        });
    });

    it('returns empty object for all undefined', () => {
        const input = { a: undefined, b: undefined };
        expect(filterUndefinedReactive(input)).toEqual({});
    });

    it('handles empty object', () => {
        expect(filterUndefinedReactive({})).toEqual({});
    });

    it('preserves original object type structure', () => {
        const input = { count: 5, name: 'test', optional: undefined };
        const result = filterUndefinedReactive(input);
        expect(result).toEqual({ count: 5, name: 'test' });
        expect('optional' in result).toBe(false);
    });
});
