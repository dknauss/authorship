import arrayMove from '../../../src/utils/arrayMove';

describe( 'arrayMove', () => {
	it( 'moves an element forward', () => {
		expect( arrayMove( [ 'a', 'b', 'c', 'd' ], 0, 2 ) ).toEqual( [ 'b', 'c', 'a', 'd' ] );
	} );

	it( 'moves an element backward', () => {
		expect( arrayMove( [ 'a', 'b', 'c', 'd' ], 2, 0 ) ).toEqual( [ 'c', 'a', 'b', 'd' ] );
	} );

	it( 'moves to the last position', () => {
		expect( arrayMove( [ 'a', 'b', 'c' ], 0, 2 ) ).toEqual( [ 'b', 'c', 'a' ] );
	} );

	it( 'handles negative to index (relative to end)', () => {
		expect( arrayMove( [ 'a', 'b', 'c', 'd' ], 0, -1 ) ).toEqual( [ 'b', 'c', 'd', 'a' ] );
	} );

	it( 'returns same order when from equals to', () => {
		expect( arrayMove( [ 'a', 'b', 'c' ], 1, 1 ) ).toEqual( [ 'a', 'b', 'c' ] );
	} );

	it( 'does not mutate the original array', () => {
		const original = [ 'a', 'b', 'c' ];
		const result = arrayMove( original, 0, 2 );
		expect( original ).toEqual( [ 'a', 'b', 'c' ] );
		expect( result ).not.toBe( original );
	} );

	it( 'works with single-element arrays', () => {
		expect( arrayMove( [ 'a' ], 0, 0 ) ).toEqual( [ 'a' ] );
	} );

	it( 'works with numeric values', () => {
		expect( arrayMove( [ 1, 2, 3, 4 ], 3, 0 ) ).toEqual( [ 4, 1, 2, 3 ] );
	} );
} );
