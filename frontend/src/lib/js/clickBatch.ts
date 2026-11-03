// Collects rapid clicks and sends them as ONE request carrying the count (AP buttons:
// three quick clicks = one request with 3 AP instead of three round trips).
// While a request is in flight, further clicks pile up and go out as the next batch.
export function clickBatcher(send: (count: number) => Promise<unknown>, delay = 250): () => void {
	let pending = 0;
	let running = false;
	let timer: ReturnType<typeof setTimeout> | undefined;

	const schedule = () => {
		clearTimeout(timer);
		timer = setTimeout(flush, delay);
	};

	async function flush() {
		if (running || pending === 0) return;
		const count = pending;
		pending = 0;
		running = true;
		try {
			await send(count);
		} finally {
			running = false;
			if (pending > 0) schedule();
		}
	}

	return () => {
		pending++;
		schedule();
	};
}
