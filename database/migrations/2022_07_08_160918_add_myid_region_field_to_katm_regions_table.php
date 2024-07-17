<?php

use App\Models\KatmRegion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMyidRegionFieldToKatmRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('katm_regions', function (Blueprint $table) {
            $table->integer('myid_region')->index()->nullable();
        });
        //Insert MYID regions
        $this->populate();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('katm_regions', function (Blueprint $table) {
            $table->dropColumn(['myid_region']);
        });
    }

    private function populate()
    {
        $myid_regions = $this->myIdRegions();
        foreach($myid_regions as $region)
        {
            KatmRegion::where('local_region',$region['local_region'])->update(['myid_region' => $region['SV_ID']]);
        }
    }

    private function myIdRegions()
    {
        return [
            [
             "SV_ID" => 1001,
             "local_region" => 198
            ],
            [
             "SV_ID" => 1003,
             "local_region" => 201
            ],
            [
             "SV_ID" => 1005,
             "local_region" => 206
            ],
            [
             "SV_ID" => 1006,
             "local_region" => 203
            ],
            [
             "SV_ID" => 1007,
             "local_region" => 208
            ],
            [
             "SV_ID" => 1008,
             "local_region" => 205
            ],
            [
             "SV_ID" => 1009,
             "local_region" => 202
            ],
            [
             "SV_ID" => 1010,
             "local_region" => 200
            ],
            [
             "SV_ID" => 1011,
             "local_region" => 199
            ],
            [
             "SV_ID" => 1012,
             "local_region" => 204
            ],
            [
             "SV_ID" => 739001080,
             "local_region" => 207
            ],
            [
             "SV_ID" => 739001160,
             "local_region" => 227
            ],
            [
             "SV_ID" => 1101,
             "local_region" => 146
            ],
            [
             "SV_ID" => 1102,
             "local_region" => 136
            ],
            [
             "SV_ID" => 1103,
             "local_region" => 141
            ],
            [
             "SV_ID" => 1104,
             "local_region" => 139
            ],
            [
             "SV_ID" => 1105,
             "local_region" => 143
            ],
            [
             "SV_ID" => 1106,
             "local_region" => 132
            ],
            [
             "SV_ID" => 1107,
             "local_region" => 140
            ],
            [
             "SV_ID" => 1108,
             "local_region" => 138
            ],
            [
             "SV_ID" => 1109,
             "local_region" => 142
            ],
            [
             "SV_ID" => 1110,
             "local_region" => 133
            ],
            [
             "SV_ID" => 1111,
             "local_region" => 137
            ],
            [
             "SV_ID" => 1112,
             "local_region" => 134
            ],
            [
             "SV_ID" => 1113,
             "local_region" => 135
            ],
            [
             "SV_ID" => 1114,
             "local_region" => 145
            ],
            [
             "SV_ID" => 1115,
             "local_region" => 144
            ],
            [
             "SV_ID" => 1116,
             "local_region" => 129
            ],
            [
             "SV_ID" => 1117,
             "local_region" => 137
            ],
            [
             "SV_ID" => 1118,
             "local_region" => 128
            ],
            [
             "SV_ID" => 1119,
             "local_region" => 127
            ],
            [
             "SV_ID" => 1120,
             "local_region" => 131
            ],
            [
             "SV_ID" => 1121,
             "local_region" => 217
            ],
            [
             "SV_ID" => 1122,
             "local_region" => 147
            ],
            [
             "SV_ID" => 739001120,
             "local_region" => 223
            ],
            [
             "SV_ID" => 739001121,
             "local_region" => 133
            ],
            [
             "SV_ID" => 1201,
             "local_region" => 123
            ],
            [
             "SV_ID" => 1202,
             "local_region" => 120
            ],
            [
             "SV_ID" => 1203,
             "local_region" => 119
            ],
            [
             "SV_ID" => 1204,
             "local_region" => 125
            ],
            [
             "SV_ID" => 1205,
             "local_region" => 124
            ],
            [
             "SV_ID" => 1206,
             "local_region" => 126
            ],
            [
             "SV_ID" => 1207,
             "local_region" => 118
            ],
            [
             "SV_ID" => 1209,
             "local_region" => 122
            ],
            [
             "SV_ID" => 1210,
             "local_region" => 114
            ],
            [
             "SV_ID" => 1211,
             "local_region" => 122
            ],
            [
             "SV_ID" => 1212,
             "local_region" => 116
            ],
            [
             "SV_ID" => 1213,
             "local_region" => 115
            ],
            [
             "SV_ID" => 1214,
             "local_region" => 113
            ],
            [
             "SV_ID" => 1215,
             "local_region" => 121
            ],
            [
             "SV_ID" => 1301,
             "local_region" => 42
            ],
            [
             "SV_ID" => 1302,
             "local_region" => 34
            ],
            [
             "SV_ID" => 1304,
             "local_region" => 38
            ],
            [
             "SV_ID" => 1305,
             "local_region" => 31
            ],
            [
             "SV_ID" => 1306,
             "local_region" => 33
            ],
            [
             "SV_ID" => 1307,
             "local_region" => 37
            ],
            [
             "SV_ID" => 1308,
             "local_region" => 32
            ],
            [
             "SV_ID" => 1309,
             "local_region" => 39
            ],
            [
             "SV_ID" => 1310,
             "local_region" => 41
            ],
            [
             "SV_ID" => 1311,
             "local_region" => 36
            ],
            [
             "SV_ID" => 1312,
             "local_region" => 35
            ],
            [
             "SV_ID" => 1313,
             "local_region" => 217
            ],
            [
             "SV_ID" => 1314,
             "local_region" => 31
            ],
            [
             "SV_ID" => 739001100,
             "local_region" => 40
            ],
            [
             "SV_ID" => 1401,
             "local_region" => 87
            ],
            [
             "SV_ID" => 1402,
             "local_region" => 88
            ],
            [
             "SV_ID" => 1403,
             "local_region" => 83
            ],
            [
             "SV_ID" => 1404,
             "local_region" => 94
            ],
            [
             "SV_ID" => 1405,
             "local_region" => 90
            ],
            [
             "SV_ID" => 1406,
             "local_region" => 85
            ],
            [
             "SV_ID" => 1407,
             "local_region" => 80
            ],
            [
             "SV_ID" => 1408,
             "local_region" => 84
            ],
            [
             "SV_ID" => 1409,
             "local_region" => 89
            ],
            [
             "SV_ID" => 1410,
             "local_region" => 93
            ],
            [
             "SV_ID" => 1411,
             "local_region" => 81
            ],
            [
             "SV_ID" => 1412,
             "local_region" => 82
            ],
            [
             "SV_ID" => 1413,
             "local_region" => 92
            ],
            [
             "SV_ID" => 1414,
             "local_region" => 86
            ],
            [
             "SV_ID" => 1415,
             "local_region" => 91
            ],
            [
             "SV_ID" => 1416,
             "local_region" => 95
            ],
            [
             "SV_ID" => 1417,
             "local_region" => 96
            ],
            [
             "SV_ID" => 1418,
             "local_region" => 96
            ],
            [
             "SV_ID" => 1419,
             "local_region" => 215
            ],
            [
             "SV_ID" => 1420,
             "local_region" => 218
            ],
            [
             "SV_ID" => 1421,
             "local_region" => 85
            ],
            [
             "SV_ID" => 1422,
             "local_region" => 219
            ],
            [
             "SV_ID" => 1423,
             "local_region" => 219
            ],
            [
             "SV_ID" => 1501,
             "local_region" => 167
            ],
            [
             "SV_ID" => 1502,
             "local_region" => 165
            ],
            [
             "SV_ID" => 1503,
             "local_region" => 157
            ],
            [
             "SV_ID" => 1504,
             "local_region" => 162
            ],
            [
             "SV_ID" => 1505,
             "local_region" => 165
            ],
            [
             "SV_ID" => 1506,
             "local_region" => 156
            ],
            [
             "SV_ID" => 1507,
             "local_region" => 158
            ],
            [
             "SV_ID" => 1508,
             "local_region" => 153
            ],
            [
             "SV_ID" => 1509,
             "local_region" => 154
            ],
            [
             "SV_ID" => 1510,
             "local_region" => 164
            ],
            [
             "SV_ID" => 1511,
             "local_region" => 160
            ],
            [
             "SV_ID" => 1512,
             "local_region" => 155
            ],
            [
             "SV_ID" => 1513,
             "local_region" => 166
            ],
            [
             "SV_ID" => 1514,
             "local_region" => 163
            ],
            [
             "SV_ID" => 1515,
             "local_region" => 152
            ],
            [
             "SV_ID" => 1516,
             "local_region" => 161
            ],
            [
             "SV_ID" => 1517,
             "local_region" => 151
            ],
            [
             "SV_ID" => 1518,
             "local_region" => 157
            ],
            [
             "SV_ID" => 1519,
             "local_region" => 148
            ],
            [
             "SV_ID" => 1520,
             "local_region" => 149
            ],
            [
             "SV_ID" => 1521,
             "local_region" => 150
            ],
            [
             "SV_ID" => 1522,
             "local_region" => 167
            ],
            [
             "SV_ID" => 2434,
             "local_region" => 159
            ],
            [
             "SV_ID" => 1601,
             "local_region" => 150
            ],
            [
             "SV_ID" => 1602,
             "local_region" => 69
            ],
            [
             "SV_ID" => 1603,
             "local_region" => 78
            ],
            [
             "SV_ID" => 1604,
             "local_region" => 71
            ],
            [
             "SV_ID" => 1605,
             "local_region" => 74
            ],
            [
             "SV_ID" => 1606,
             "local_region" => 75
            ],
            [
             "SV_ID" => 1607,
             "local_region" => 79
            ],
            [
             "SV_ID" => 1608,
             "local_region" => 77
            ],
            [
             "SV_ID" => 1609,
             "local_region" => 70
            ],
            [
             "SV_ID" => 1610,
             "local_region" => 76
            ],
            [
             "SV_ID" => 1611,
             "local_region" => 72
            ],
            [
             "SV_ID" => 1612,
             "local_region" => 73
            ],
            [
             "SV_ID" => 1613,
             "local_region" => 68
            ],
            [
             "SV_ID" => 1614,
             "local_region" => 70
            ],
            [
             "SV_ID" => 1615,
             "local_region" => 75
            ],
            [
             "SV_ID" => 1616,
             "local_region" => 79
            ],
            [
             "SV_ID" => 1617,
             "local_region" => 76
            ],
            [
             "SV_ID" => 1618,
             "local_region" => 68
            ],
            [
             "SV_ID" => 1701,
             "local_region" => 6
            ],
            [
             "SV_ID" => 1702,
             "local_region" => 16
            ],
            [
             "SV_ID" => 1703,
             "local_region" => 7
            ],
            [
             "SV_ID" => 1704,
             "local_region" => 8
            ],
            [
             "SV_ID" => 1705,
             "local_region" => 9
            ],
            [
             "SV_ID" => 1706,
             "local_region" => 210
            ],
            [
             "SV_ID" => 1707,
             "local_region" => 214
            ],
            [
             "SV_ID" => 1708,
             "local_region" => 14
            ],
            [
             "SV_ID" => 1709,
             "local_region" => 11
            ],
            [
             "SV_ID" => 1710,
             "local_region" => 18
            ],
            [
             "SV_ID" => 1711,
             "local_region" => 15
            ],
            [
             "SV_ID" => 1712,
             "local_region" => 12
            ],
            [
             "SV_ID" => 1713,
             "local_region" => 10
            ],
            [
             "SV_ID" => 1714,
             "local_region" => 17
            ],
            [
             "SV_ID" => 1715,
             "local_region" => 1
            ],
            [
             "SV_ID" => 1716,
             "local_region" => 2
            ],
            [
             "SV_ID" => 1717,
             "local_region" => 3
            ],
            [
             "SV_ID" => 1718,
             "local_region" => 5
            ],
            [
             "SV_ID" => 1719,
             "local_region" => 4
            ],
            [
             "SV_ID" => 739001140,
             "local_region" => 1
            ],
            [
             "SV_ID" => 1801,
             "local_region" => 50
            ],
            [
             "SV_ID" => 1802,
             "local_region" => 48
            ],
            [
             "SV_ID" => 1803,
             "local_region" => 213
            ],
            [
             "SV_ID" => 1804,
             "local_region" => 46
            ],
            [
             "SV_ID" => 1805,
             "local_region" => 47
            ],
            [
             "SV_ID" => 1806,
             "local_region" => 51
            ],
            [
             "SV_ID" => 1807,
             "local_region" => 57
            ],
            [
             "SV_ID" => 1808,
             "local_region" => 45
            ],
            [
             "SV_ID" => 1809,
             "local_region" => 44
            ],
            [
             "SV_ID" => 1810,
             "local_region" => 54
            ],
            [
             "SV_ID" => 1811,
             "local_region" => 52
            ],
            [
             "SV_ID" => 1812,
             "local_region" => 53
            ],
            [
             "SV_ID" => 1813,
             "local_region" => 56
            ],
            [
             "SV_ID" => 1814,
             "local_region" => 221
            ],
            [
             "SV_ID" => 1815,
             "local_region" => 43
            ],
            [
             "SV_ID" => 1816,
             "local_region" => 49
            ],
            [
             "SV_ID" => 1901,
             "local_region" => 110
            ],
            [
             "SV_ID" => 1902,
             "local_region" => 99
            ],
            [
             "SV_ID" => 1903,
             "local_region" => 102
            ],
            [
             "SV_ID" => 1904,
             "local_region" => 106
            ],
            [
             "SV_ID" => 1905,
             "local_region" => 49
            ],
            [
             "SV_ID" => 1906,
             "local_region" => 102
            ],
            [
             "SV_ID" => 1907,
             "local_region" => 111
            ],
            [
             "SV_ID" => 1908,
             "local_region" => 109
            ],
            [
             "SV_ID" => 1909,
             "local_region" => 101
            ],
            [
             "SV_ID" => 1910,
             "local_region" => 108
            ],
            [
             "SV_ID" => 1911,
             "local_region" => 107
            ],
            [
             "SV_ID" => 1912,
             "local_region" => 103
            ],
            [
             "SV_ID" => 1913,
             "local_region" => 105
            ],
            [
             "SV_ID" => 1914,
             "local_region" => 100
            ],
            [
             "SV_ID" => 1915,
             "local_region" => 112
            ],
            [
             "SV_ID" => 1916,
             "local_region" => 98
            ],
            [
             "SV_ID" => 1917,
             "local_region" => 100
            ],
            [
             "SV_ID" => 2001,
             "local_region" => 24
            ],
            [
             "SV_ID" => 2002,
             "local_region" => 20
            ],
            [
             "SV_ID" => 2003,
             "local_region" => 27
            ],
            [
             "SV_ID" => 2004,
             "local_region" => 19
            ],
            [
             "SV_ID" => 2005,
             "local_region" => 25
            ],
            [
             "SV_ID" => 2006,
             "local_region" => 23
            ],
            [
             "SV_ID" => 2007,
             "local_region" => 26
            ],
            [
             "SV_ID" => 2008,
             "local_region" => 28
            ],
            [
             "SV_ID" => 2009,
             "local_region" => 21
            ],
            [
             "SV_ID" => 2010,
             "local_region" => 22
            ],
            [
             "SV_ID" => 2011,
             "local_region" => 29
            ],
            [
             "SV_ID" => 2012,
             "local_region" => 21
            ],
            [
             "SV_ID" => 2013,
             "local_region" => 30
            ],
            [
             "SV_ID" => 2014,
             "local_region" => 21
            ],
            [
             "SV_ID" => 2015,
             "local_region" => 220
            ],
            [
             "SV_ID" => 2016,
             "local_region" => 30
            ],
            [
             "SV_ID" => 2017,
             "local_region" => 30
            ],
            [
             "SV_ID" => 2101,
             "local_region" => 60
            ],
            [
             "SV_ID" => 2102,
             "local_region" => 62
            ],
            [
             "SV_ID" => 2104,
             "local_region" => 67
            ],
            [
             "SV_ID" => 2105,
             "local_region" => 64
            ],
            [
             "SV_ID" => 2106,
             "local_region" => 58
            ],
            [
             "SV_ID" => 2107,
             "local_region" => 65
            ],
            [
             "SV_ID" => 2108,
             "local_region" => 66
            ],
            [
             "SV_ID" => 2109,
             "local_region" => 63
            ],
            [
             "SV_ID" => 2110,
             "local_region" => 61
            ],
            [
             "SV_ID" => 2111,
             "local_region" => 58
            ],
            [
             "SV_ID" => 2112,
             "local_region" => 59
            ],
            [
             "SV_ID" => 2113,
             "local_region" => 60
            ],
            [
             "SV_ID" => 2201,
             "local_region" => 173
            ],
            [
             "SV_ID" => 2202,
             "local_region" => 175
            ],
            [
             "SV_ID" => 2203,
             "local_region" => 171
            ],
            [
             "SV_ID" => 2204,
             "local_region" => 178
            ],
            [
             "SV_ID" => 2205,
             "local_region" => 177
            ],
            [
             "SV_ID" => 2206,
             "local_region" => 174
            ],
            [
             "SV_ID" => 2207,
             "local_region" => 172
            ],
            [
             "SV_ID" => 2208,
             "local_region" => 179
            ],
            [
             "SV_ID" => 2209,
             "local_region" => 176
            ],
            [
             "SV_ID" => 2210,
             "local_region" => 212
            ],
            [
             "SV_ID" => 2211,
             "local_region" => 178
            ],
            [
             "SV_ID" => 2212,
             "local_region" => 169
            ],
            [
             "SV_ID" => 2213,
             "local_region" => 170
            ],
            [
             "SV_ID" => 739001141,
             "local_region" => 226
            ],
            [
             "SV_ID" => 2301,
             "local_region" => 216
            ],
            [
             "SV_ID" => 2302,
             "local_region" => 184
            ],
            [
             "SV_ID" => 2303,
             "local_region" => 185
            ],
            [
             "SV_ID" => 2305,
             "local_region" => 188
            ],
            [
             "SV_ID" => 2306,
             "local_region" => 194
            ],
            [
             "SV_ID" => 2307,
             "local_region" => 182
            ],
            [
             "SV_ID" => 2308,
             "local_region" => 209
            ],
            [
             "SV_ID" => 2309,
             "local_region" => 191
            ],
            [
             "SV_ID" => 2310,
             "local_region" => 192
            ],
            [
             "SV_ID" => 2311,
             "local_region" => 190
            ],
            [
             "SV_ID" => 2312,
             "local_region" => 183
            ],
            [
             "SV_ID" => 2313,
             "local_region" => 187
            ],
            [
             "SV_ID" => 2314,
             "local_region" => 189
            ],
            [
             "SV_ID" => 2315,
             "local_region" => 195
            ],
            [
             "SV_ID" => 2316,
             "local_region" => 193
            ],
            [
             "SV_ID" => 2317,
             "local_region" => 180
            ],
            [
             "SV_ID" => 2318,
             "local_region" => 191
            ],
            [
             "SV_ID" => 2319,
             "local_region" => 184
            ],
            [
             "SV_ID" => 2320,
             "local_region" => 181
            ],
            [
             "SV_ID" => 2321,
             "local_region" => 188
            ],
            [
             "SV_ID" => 2322,
             "local_region" => 189
            ],
            [
             "SV_ID" => 2323,
             "local_region" => 190
            ]
        ];
    }
}
