/**
 * Server Turizm — Umrah Control Panel
 * Independent Apps Script stack (NO Tour functions).
 * Version: 1.0.0
 */
var ST_UMRAH = Object.freeze({
  VERSION: '1.0.0',
  SCHEMA_VERSION: '1.0.0',
  HOME_SHEET: 'Home',
  HOTEL_SHEET: 'ST Hotel Directory',
  STATE_SHEET: 'ST Umrah Sync State',
  TIMEZONE: 'Europe/Istanbul',
  MAX_COLUMNS: 36,

  // Existing Umrah sheet contract (A:AJ).
  COL_PROGRAM_NO: 2,          // B
  COL_TIER: 3,                // C
  COL_TITLE: 4,               // D
  COL_COUNTER_LABEL: 5,       // E
  COL_START_DATE: 7,          // G
  COL_TRANSFER_1: 8,          // H
  COL_TRANSFER_2: 9,          // I
  COL_END_DATE: 10,           // J
  COL_DURATION_ROUTE: 11,     // K
  COL_OTHER_CITY: 12,         // L
  COL_OTHER_HOTEL: 13,        // M
  COL_MEDINAH_HOTEL: 16,      // P
  COL_MAKKAH_HOTEL: 19,       // S
  COL_PRICE_DOUBLE: 22,       // V
  COL_PRICE_TRIPLE: 23,       // W
  COL_PRICE_QUAD: 24,         // X
  COL_CHILD_RULES: 25,        // Y
  COL_IMPORTANT_NOTES: 26,    // Z
  COL_PHONE: 27,              // AA
  COL_CAPACITY: 28,           // AB
  COL_SOLD_OUT: 29,           // AC
  COL_REMOVE: 30,             // AD = Programı Kaldır (ONLY removal control)
  COL_MEDINAH_MEAL: 31,       // AE
  COL_MAKKAH_MEAL: 32,        // AF
  COL_MEDINAH_ROOM: 33,       // AG
  COL_MAKKAH_ROOM: 34,        // AH
  COL_HERO_IMAGE: 35,         // AI
  COL_LOGO: 36,               // AJ

  REMOVE_HEADER: 'Programı Kaldır'
});

var ST_UMRAH_SYNC = Object.freeze({
  VERSION: '1.0.0',
  CONTRACT: 'ST-DIRECT-SYNC-1.0.0',
  ENDPOINT_PROPERTY: 'ST_DIRECT_SYNC_ENDPOINT',
  KEY_ID_PROPERTY: 'ST_DIRECT_SYNC_KEY_ID',
  SECRET_PROPERTY: 'ST_DIRECT_SYNC_SECRET',
  TRANSPORT_MAX_ATTEMPTS: 3,
  TRANSPORT_RETRY_DELAY_MS: 1500
});
