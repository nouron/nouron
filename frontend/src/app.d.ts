// See https://svelte.dev/docs/kit/types#app.d.ts
// for information about these interfaces
declare global {
	namespace App {
		// interface Error {}
		// interface Locals {}
		// interface PageData {}
		interface PageState {
			/** Id of the open MobileSheet (back button closes it). */
			sheet?: string;
		}
		// interface Platform {}
	}
}

export {};
