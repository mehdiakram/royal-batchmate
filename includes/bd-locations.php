<?php
/**
 * Bangladesh Districts and Upazilas / Thanas Data & Helpers
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Return all 64 Bangladesh districts sorted alphabetically
 */
function BatchMate_get_bd_districts(): array {
    return [
        'Bagerhat', 'Bandarban', 'Barguna', 'Barishal', 'Bhola', 'Bogura', 'Brahmanbaria',
        'Chandpur', 'Chapai Nawabganj', 'Chattogram', 'Chuadanga', "Cox's Bazar", 'Cumilla',
        'Dhaka', 'Dinajpur', 'Faridpur', 'Feni', 'Gaibandha', 'Gazipur', 'Gopalganj',
        'Habiganj', 'Jamalpur', 'Jashore', 'Jhalokati', 'Jhenaidah', 'Joypurhat',
        'Khagrachari', 'Khulna', 'Kishoreganj', 'Kurigram', 'Kushtia', 'Lakshmipur',
        'Lalmonirhat', 'Madaripur', 'Magura', 'Manikganj', 'Meherpur', 'Moulvibazar',
        'Munshiganj', 'Mymensingh', 'Naogaon', 'Narail', 'Narayanganj', 'Narsingdi',
        'Natore', 'Netrokona', 'Nilphamari', 'Noakhali', 'Pabna', 'Panchagarh',
        'Patuakhali', 'Pirojpur', 'Rajbari', 'Rajshahi', 'Rangamati', 'Rangpur',
        'Satkhira', 'Shariatpur', 'Sherpur', 'Sirajganj', 'Sunamganj', 'Sylhet',
        'Tangail', 'Thakurgaon'
    ];
}

/**
 * Return mapping of Districts => Upazilas / Thanas
 */
function BatchMate_get_bd_upazilas(): array {
    static $data = null;
    if ( $data !== null ) {
        return $data;
    }

    $data = [
        'Bagerhat' => [
            'Bagerhat Sadar', 'Chitalmari', 'Fakirhat', 'Kachua', 'Mollahat', 'Mongla', 'Morrelganj', 'Rampal', 'Sarankhola'
        ],
        'Bandarban' => [
            'Bandarban Sadar', 'Alikadam', 'Naikhyangchhari', 'Rowangchhari', 'Ruma', 'Thanchi', 'Lama'
        ],
        'Barguna' => [
            'Barguna Sadar', 'Amtali', 'Bamna', 'Betagi', 'Patharghata', 'Taltali'
        ],
        'Barishal' => [
            'Barishal Sadar', 'Agailjhara', 'Babuganj', 'Bakerganj', 'Banaripara', 'Gaurnadi', 'Hizla', 'Mehendiganj', 'Muladi', 'Wazirpur'
        ],
        'Bhola' => [
            'Bhola Sadar', 'Burhanuddin', 'Char Fasson', 'Daulatkhan', 'Lalmohan', 'Manpura', 'Tazumuddin'
        ],
        'Bogura' => [
            'Bogura Sadar', 'Adamdighi', 'Dhunat', 'Dhupchanchia', 'Gabtali', 'Kahaloo', 'Nandigram', 'Sariakandi', 'Shajahanpur', 'Sherpur', 'Shibganj', 'Sonatola'
        ],
        'Brahmanbaria' => [
            'Brahmanbaria Sadar', 'Akhaura', 'Ashuganj', 'Bancharampur', 'Bijoynagar', 'Kasba', 'Nabinagar', 'Nasirnagar', 'Sarail'
        ],
        'Chandpur' => [
            'Chandpur Sadar', 'Faridganj', 'Haimchar', 'Hajiganj', 'Kachua', 'Matlab North', 'Matlab South', 'Shahrasti'
        ],
        'Chapai Nawabganj' => [
            'Bholahat', 'Gomastapur', 'Nachole', 'Nawabganj Sadar', 'Shibganj'
        ],
        'Chattogram' => [
            'Anwara', 'Banshkhali', 'Boalkhali', 'Chandanaish', 'Fatikchhari', 'Hathazari', 'Karnaphuli', 'Lohagara', 'Mirsharai', 'Patiya', 'Rangunia', 'Raozan', 'Sandwip', 'Satkania', 'Sitakunda', 'Bakalia', 'Bandar', 'Bayazid', 'Chandgaon', 'Double Mooring', 'Halishahar', 'Khulshi', 'Kotwali', 'Pahartali', 'Panchlaish', 'Patenga'
        ],
        'Chuadanga' => [
            'Chuadanga Sadar', 'Alamdanga', 'Damurhuda', 'Jibannagar'
        ],
        "Cox's Bazar" => [
            "Cox's Bazar Sadar", 'Chakaria', 'Eidgaon', 'Kutubdia', 'Maheshkhali', 'Pekua', 'Ramu', 'Teknaf', 'Ukhia'
        ],
        'Cumilla' => [
            'Barura', 'Brahmanpara', 'Burichang', 'Chandina', 'Chauddagram', 'Cumilla Adarsha Sadar', 'Cumilla Sadar Dakshin', 'Daudkandi', 'Debidwar', 'Homna', 'Laksam', 'Lalmai', 'Meghna', 'Monohargonj', 'Muradnagar', 'Nangalkot', 'Titas'
        ],
        'Dhaka' => [
            'Dhamrai', 'Dohar', 'Keraniganj', 'Nawabganj', 'Savar', 'Badda', 'Banani', 'Bangshal', 'Cantonment', 'Chalkbazar', 'Dakshinkhan', 'Darus Salam', 'Demra', 'Dhanmondi', 'Gendaria', 'Gulshan', 'Hazaribagh', 'Jatrabari', 'Kadamtali', 'Kafrul', 'Kalabagan', 'Kamrangirchar', 'Khilgaon', 'Khilkhet', 'Kotwali', 'Lalbagh', 'Mirpur', 'Mohammadpur', 'Motijheel', 'New Market', 'Pallabi', 'Paltan', 'Ramna', 'Rampura', 'Sabujbagh', 'Shah Ali', 'Shahbagh', 'Sher-e-Bangla Nagar', 'Shyampur', 'Sutrapur', 'Tejgaon', 'Tejgaon Industrial Area', 'Turag', 'Uttara', 'Uttarkhan', 'Vatara', 'Wari'
        ],
        'Dinajpur' => [
            'Dinajpur Sadar', 'Birampur', 'Birganj', 'Birol', 'Bochaganj', 'Chirirbandar', 'Fulbari', 'Ghoraghat', 'Hakimpur', 'Kaharole', 'Khansama', 'Nawabganj', 'Parbatipur'
        ],
        'Faridpur' => [
            'Faridpur Sadar', 'Alfadanga', 'Bhanga', 'Boalmari', 'Charbhadrasan', 'Madhukhali', 'Nagarkanda', 'Sadarpur', 'Saltha'
        ],
        'Feni' => [
            'Feni Sadar', 'Chhagalnaiya', 'Daganbhuiyan', 'Fulgazi', 'Parshuram', 'Sonagazi'
        ],
        'Gaibandha' => [
            'Gaibandha Sadar', 'Fulchhari', 'Gobindaganj', 'Palashbari', 'Sadullapur', 'Sughatta', 'Sundarganj'
        ],
        'Gazipur' => [
            'Gazipur Sadar', 'Kaliakair', 'Kaliganj', 'Kapasia', 'Sreepur', 'Tongi'
        ],
        'Gopalganj' => [
            'Gopalganj Sadar', 'Kashiani', 'Kotalipara', 'Muksudpur', 'Tungipara'
        ],
        'Habiganj' => [
            'Habiganj Sadar', 'Ajmiriganj', 'Bahubal', 'Baniyachong', 'Chunarughat', 'Lakhai', 'Madhabpur', 'Nabiganj', 'Sayestaganj'
        ],
        'Jamalpur' => [
            'Jamalpur Sadar', 'Bakshiganj', 'Dewanganj', 'Islampur', 'Madarganj', 'Melandaha', 'Sarishabari'
        ],
        'Jashore' => [
            'Jashore Sadar', 'Abhaynagar', 'Bagherpara', 'Chaugachha', 'Jhikargachha', 'Keshabpur', 'Manirampur', 'Sharsha'
        ],
        'Jhalokati' => [
            'Jhalokati Sadar', 'Kathalia', 'Nalchhiti', 'Rajapur'
        ],
        'Jhenaidah' => [
            'Jhenaidah Sadar', 'Harinakundu', 'Kaliganj', 'Kotchandpur', 'Maheshpur', 'Shailkupa'
        ],
        'Joypurhat' => [
            'Joypurhat Sadar', 'Akkelpur', 'Kalai', 'Khetlal', 'Panchbibi'
        ],
        'Khagrachari' => [
            'Khagrachari Sadar', 'Dighinala', 'Guimara', 'Lakshmichhari', 'Mahalchhari', 'Manikchhari', 'Matiranga', 'Panchhari', 'Ramgarh'
        ],
        'Khulna' => [
            'Batiaghata', 'Dacope', 'Dighalia', 'Dumuria', 'Koyra', 'Paikgachha', 'Phultala', 'Rupsha', 'Terokhada', 'Daulatpur', 'Khalishpur', 'Khan Jahan Ali', 'Kotwali', 'Sonadanga'
        ],
        'Kishoreganj' => [
            'Kishoreganj Sadar', 'Ashtagram', 'Bajitpur', 'Bhairab', 'Hossainpur', 'Itna', 'Karimganj', 'Katiadi', 'Kuliarchar', 'Mithamain', 'Nikli', 'Pakundia', 'Tarail'
        ],
        'Kurigram' => [
            'Kurigram Sadar', 'Bhurungamari', 'Char Rajibpur', 'Chilmari', 'Nageshwari', 'Phulbari', 'Rajarhat', 'Raomari', 'Ulipur'
        ],
        'Kushtia' => [
            'Kushtia Sadar', 'Bheramara', 'Daulatpur', 'Khoksa', 'Kumarkhali', 'Mirpur'
        ],
        'Lakshmipur' => [
            'Lakshmipur Sadar', 'Kamalnagar', 'Raipur', 'Ramganj', 'Ramgati'
        ],
        'Lalmonirhat' => [
            'Lalmonirhat Sadar', 'Aditmari', 'Hatibandha', 'Kaliganj', 'Patgram'
        ],
        'Madaripur' => [
            'Madaripur Sadar', 'Dasar', 'Kalkini', 'Rajoir', 'Shibchar'
        ],
        'Magura' => [
            'Magura Sadar', 'Mohammadpur', 'Shalikha', 'Sreepur'
        ],
        'Manikganj' => [
            'Manikganj Sadar', 'Daulatpur', 'Ghior', 'Harirampur', 'Saturia', 'Shivalaya', 'Singair'
        ],
        'Meherpur' => [
            'Meherpur Sadar', 'Gangni', 'Mujibnagar'
        ],
        'Moulvibazar' => [
            'Moulvibazar Sadar', 'Barlekha', 'Juri', 'Kamalganj', 'Kulaura', 'Rajnagar', 'Sreemangal'
        ],
        'Munshiganj' => [
            'Munshiganj Sadar', 'Gazaria', 'Louhajang', 'Sirajdikhan', 'Sreenagar', 'Tongibari'
        ],
        'Mymensingh' => [
            'Mymensingh Sadar', 'Bhaluka', 'Dhobaura', 'Fulbaria', 'Gafargaon', 'Gauripur', 'Haluaghat', 'Ishwarganj', 'Muktagachha', 'Nandail', 'Phulpur', 'Tara Khanda', 'Trishal'
        ],
        'Naogaon' => [
            'Naogaon Sadar', 'Atrai', 'Badalgachhi', 'Dhamoirhat', 'Manda', 'Mohadevpur', 'Niamatpur', 'Patnitala', 'Porsha', 'Raninagar', 'Sapahar'
        ],
        'Narail' => [
            'Narail Sadar', 'Kalia', 'Lohagara'
        ],
        'Narayanganj' => [
            'Narayanganj Sadar', 'Araihazar', 'Bandar', 'Rupganj', 'Sonargaon'
        ],
        'Narsingdi' => [
            'Narsingdi Sadar', 'Belabo', 'Monohardi', 'Palash', 'Raipura', 'Shibpur'
        ],
        'Natore' => [
            'Natore Sadar', 'Bagatipara', 'Baraigram', 'Gurudaspur', 'Lalpur', 'Naldanga', 'Singra'
        ],
        'Netrokona' => [
            'Netrokona Sadar', 'Atpara', 'Barhatta', 'Durgapur', 'Kalmakanda', 'Kendua', 'Khaliajuri', 'Madan', 'Mohanganj', 'Purbadhala'
        ],
        'Nilphamari' => [
            'Nilphamari Sadar', 'Dimla', 'Domar', 'Jaldhaka', 'Kishoreganj', 'Saidpur'
        ],
        'Noakhali' => [
            'Noakhali Sadar', 'Begumganj', 'Chatkhil', 'Companiganj', 'Hatiya', 'Kabirhat', 'Senbagh', 'Sonaimuri', 'Subarnachar'
        ],
        'Pabna' => [
            'Pabna Sadar', 'Atgharia', 'Bera', 'Bhangura', 'Chatmohar', 'Faridpur', 'Ishwardi', 'Santhia', 'Sujanagar'
        ],
        'Panchagarh' => [
            'Panchagarh Sadar', 'Atwari', 'Boda', 'Debiganj', 'Tetulia'
        ],
        'Patuakhali' => [
            'Patuakhali Sadar', 'Bauphal', 'Dashmina', 'Dumki', 'Galachipa', 'Kalapara', 'Mirzaganj', 'Rangabali'
        ],
        'Pirojpur' => [
            'Pirojpur Sadar', 'Bhandaria', 'Indurkani', 'Kawkhali', 'Mathbaria', 'Nazirpur', 'Nesarabad (Swarupkati)'
        ],
        'Rajbari' => [
            'Rajbari Sadar', 'Baliakandi', 'Goalandaghat', 'Kalukhali', 'Pangsha'
        ],
        'Rajshahi' => [
            'Bagha', 'Bagmara', 'Charghat', 'Durgapur', 'Godagari', 'Mohanpur', 'Paba', 'Puthia', 'Tanore', 'Boalia', 'Matihar', 'Rajpara', 'Shah Mokdum'
        ],
        'Rangamati' => [
            'Rangamati Sadar', 'Baghaichhari', 'Barkal', 'Belaichhari', 'Juraichhari', 'Kaptai', 'Kawkhali', 'Langadu', 'Naniarchar', 'Rajasthali'
        ],
        'Rangpur' => [
            'Rangpur Sadar', 'Badarganj', 'Gangachhara', 'Kaunia', 'Mithapukur', 'Pirgachha', 'Pirganj', 'Taraganj'
        ],
        'Satkhira' => [
            'Satkhira Sadar', 'Assasuni', 'Debhata', 'Kalaroa', 'Kaliganj', 'Shyamnagar', 'Tala'
        ],
        'Shariatpur' => [
            'Shariatpur Sadar', 'Bhedarganj', 'Damudya', 'Gosairhat', 'Naria', 'Zanjira'
        ],
        'Sherpur' => [
            'Sherpur Sadar', 'Jhenaigati', 'Nakla', 'Nalitabari', 'Sreebardi'
        ],
        'Sirajganj' => [
            'Sirajganj Sadar', 'Belkuchi', 'Chauhali', 'Kamarkhanda', 'Kazipur', 'Raiganj', 'Shahjadpur', 'Tarash', 'Ullahpara'
        ],
        'Sunamganj' => [
            'Sunamganj Sadar', 'Bishwamvarpur', 'Chhatak', 'Derai', 'Dharamapasha', 'Dowarabazar', 'Jagannathpur', 'Jamalganj', 'Madhyanagar', 'Shantiganj', 'Sullah', 'Tahirpur'
        ],
        'Sylhet' => [
            'Sylhet Sadar', 'Balaganj', 'Beanibazar', 'Bishwanath', 'Companiganj', 'Fenchuganj', 'Golapganj', 'Gowainghat', 'Jaintiapur', 'Kanaighat', 'Osmani Nagar', 'South Surma', 'Zakiganj'
        ],
        'Tangail' => [
            'Tangail Sadar', 'Basail', 'Bhuapur', 'Delduar', 'Dhanbari', 'Ghatail', 'Gopalpur', 'Kalihati', 'Madhupur', 'Mirzapur', 'Nagarpur', 'Sakhipur'
        ],
        'Thakurgaon' => [
            'Thakurgaon Sadar', 'Baliadangi', 'Haripur', 'Pirganj', 'Ranisankail', 'Ranishankail'
        ],
    ];

    return $data;
}

/**
 * Return upazilas for a single district
 */
function BatchMate_get_upazilas_by_district( string $district ): array {
    $district = trim( $district );
    if ( empty( $district ) ) return [];

    $all = BatchMate_get_bd_upazilas();
    if ( isset( $all[ $district ] ) ) {
        return $all[ $district ];
    }

    // Case-insensitive lookup
    foreach ( $all as $d => $upz ) {
        if ( strcasecmp( $d, $district ) === 0 ) {
            return $upz;
        }
    }

    return [];
}

/**
 * AJAX handler to get upazilas by district
 */
function BatchMate_get_upazilas_ajax(): void {
    $district = sanitize_text_field( $_POST['district'] ?? '' );
    $upazilas = BatchMate_get_upazilas_by_district( $district );
    wp_send_json_success( $upazilas );
}
add_action( 'wp_ajax_batchmate_get_upazilas',        'BatchMate_get_upazilas_ajax' );
add_action( 'wp_ajax_nopriv_batchmate_get_upazilas', 'BatchMate_get_upazilas_ajax' );
