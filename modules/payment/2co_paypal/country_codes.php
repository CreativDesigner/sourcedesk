<?php
// Country codes for 2Checkout

function find2co_cc($countryName) {
	switch ($countryName) {
	case 'AT':case 'Österreich':case 'Austria':
		return "AUT";
		break;

	case 'BE':case 'Belgien':case 'Belgium':
		return "BEL";
		break;

	case 'BG':case 'Bulgarien':case 'Bulgaria':
		return "BGR";
		break;

	case 'DE':case 'Deutschland':case 'Germany':
		return "DEU";
		break;

	case 'DK':case 'Dänemark':case 'Denmark':
		return "DNK";
		break;

	case 'EE':case 'Estland':case 'Estonia':
		return "EST";
		break;

	case 'FI':case 'SF':case 'Finnland':case 'Finland':
		return "FIN";
		break;

	case 'FR':case 'FX':case 'FXX':case 'Frankreich':case 'France':
		return "FRA";
		break;

	case 'GR':case 'Griechenland':case 'Greece':
		return "GRC";
		break;

	case 'IE':case 'Irland':case 'Ireland':
		return "IRL";
		break;

	case 'IT':case 'Italien':case 'Italy':
		return "ITA";
		break;

	case 'HR':case 'Kroatien':case 'Croatia':
		return "HRV";
		break;

	case 'LV':case 'Lettland':case 'Latvia':
		return "LVA";
		break;

	case 'LT':case 'Litauen':case 'Lithuania':
		return "LTU";
		break;

	case 'LU':case 'Luxemburg':case 'Luxembourg':
		return "LUX";
		break;

	case 'MT':case 'Malta':
		return "MLT";
		break;

	case 'NZ':case 'Neuseeland':case 'New Zealand':
		return "NZL";
		break;

	case 'NL':case 'Niederlande':case 'Netherlands':
		return "NLD";
		break;

	case 'PL':case 'Polen':case 'Poland':
		return "POL";
		break;

	case 'PT':case 'Portugal':
		return "PRT";
		break;

	case 'RO':case 'Rumänien':case 'Romania':
		return "ROU";
		break;

	case 'SE':case 'Schweden':case 'Sweden':
		return "SWE";
		break;

	case 'CH':case 'Schweiz':case 'Switzerland':
		return "CHE";
		break;

	case 'SK':case 'Slowakei':case 'Slowakische Repulik':case 'Slovakia':
		return "SVK";
		break;

	case 'SI':case 'Slowenien':case 'Slovenia':
		return "SVN";
		break;

	case 'ES':case 'Spanien':case 'Spain':
		return "ESP";
		break;

	case 'CZ':case 'Tschechische Republik':case 'Czech Republic':
		return "CZE";
		break;

	case 'TR':case 'Türkei':case 'Turkey':
		return "TUR";
		break;

	case 'HU':case 'Ungarn':case 'Hungary':
		return "HUN";
		break;

	case 'GB':case 'UK':case 'Vereinigtes Königreich':case 'Großbritannien':case 'United Kingdom':case 'Great Britain':
		return "GBR";
		break;

	case 'CY':case 'Zypern':case 'Cyprus':
		return "CYP";
		break;

	default:
		return $countryName;
	}
}

?>