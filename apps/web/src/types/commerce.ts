export interface CommerceProduct {
	readonly schemaVersion: 1;
	readonly courseKey: string;
	readonly name: string;
	readonly priceMinor: number;
	readonly currency: string;
	readonly currencyDecimals: number;
	readonly purchasable: boolean;
	readonly checkoutUrl: string;
}
