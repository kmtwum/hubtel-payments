
import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'hubtel_data', {} );

const defaultLabel = __(
	'Mobile Money / Card',
	'woo-gutenberg-products-block'
);

const label = decodeEntities( settings.title ) || defaultLabel;
const logo = decodeEntities( settings.logo ) || defaultLabel;

/**
 * Content component
 */
const Content = () => {
	return decodeEntities( settings.description || '' );
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <>
		<PaymentMethodLabel text={ label } />
		<img src={logo} alt='Payment Options'/>
		</>;
};

/**
 * Hubtel payment method config object.
 */
const Hubtel = {
	name: "hubtel-payments",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( Hubtel );
