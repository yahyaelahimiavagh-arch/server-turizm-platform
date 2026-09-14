#!/usr/bin/env node
'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'integrations/google-sheets/ST_TDE_Exporter.gs'), 'utf8');
const context = {
  console,
  Utilities: {
    formatDate(value) {
      return new Date(value).toISOString().slice(0, 10);
    }
  }
};
vm.createContext(context);
vm.runInContext(source, context, {filename: 'ST_TDE_Exporter.gs'});

function blankRow() {
  return Array(36).fill('');
}

function makeUmrah219() {
  const row = blankRow();
  row[1] = '219';
  row[2] = 'Lüks';
  row[3] = 'Lüks Umre Programı';
  row[6] = '10.09.2026';
  row[8] = '13.09.2026';
  row[9] = '19.09.2026';
  row[10] = '9 Gece 10 Gün\n3 Gece Medine, 6 Gece Mekke';
  row[15] = 'Mias Al Madina Hotel';
  row[18] = 'Dar Al Ghufran Safwah Tower Hotel';
  row[21] = 2400;
  row[22] = 2350;
  row[23] = 2300;
  row[24] = '0-2 Yaş: 700$\n2-6 Yaş: 1000$\n6-11 Yaş: 1200$\nYATAK DAHİL DEĞİLDİR';
  row[25] = 'AÇIK BÜFE\nYAKIN SERVİSLİ';
  row[26] = '0530 201 52 84';
  row[27] = 25;
  row[30] = 'H.B';
  row[31] = 'F.B';
  row[34] = 'https://www.serverturizm.com.tr/example.webp';
  return row;
}

const hotelIndex = {
  byName: {
    'mias al madina hotel': 'STH-000001',
    'dar al ghufran safwah tower hotel': 'STH-000002',
    'azal pyramids hotel': 'STH-000003'
  },
  ambiguous: {}
};

{
  const row = makeUmrah219();
  const issues = {errors: [], warnings: []};
  const program = context.stTdeBuildProgram_(row, row, 3, hotelIndex, issues);
  assert.equal(program.program_code, '219');
  assert.equal(program.service_type, 'umrah');
  assert.equal(program.schedule.duration_nights, 9);
  assert.deepEqual(JSON.parse(JSON.stringify(program.destinations)), [
    {sequence: 1, country: 'Saudi Arabia', city: 'Madinah', nights: 3},
    {sequence: 2, country: 'Saudi Arabia', city: 'Makkah', nights: 6}
  ]);
  assert.equal(program.stays[0].hotel_id, 'STH-000001');
  assert.equal(program.stays[1].hotel_id, 'STH-000002');
  assert.equal(program.stays[0].check_in, '2026-09-10');
  assert.equal(program.stays[0].check_out, '2026-09-13');
  assert.equal(program.stays[1].check_out, '2026-09-19');
  assert.equal(program.segments[1].notes, 'Source date slot I');
  assert.equal(program.pricing.entries.length, 3);
  assert.equal(program.pricing.child_rules.length, 3);
  assert.equal(program.pricing.child_rules[0].bed_included, false);
  assert.equal(program.inclusions.length, 2);
  assert.equal(program.contact.phone, '+905302015284');
  assert.equal(issues.errors.length, 0);
}

{
  const row = makeUmrah219();
  row[1] = 'KAHIRE3';
  row[7] = '03.10.2026';
  row[8] = '10.10.2026';
  row[9] = '13.10.2026';
  row[10] = '13 Gece 14 Gün\n3 Gece Kahire, 7 Gece Mekke, 3 Gece Medine';
  row[12] = 'Azal Pyramids Hotel';
  const issues = {errors: [], warnings: []};
  const program = context.stTdeBuildProgram_(row, row, 4, hotelIndex, issues);
  assert.equal(program.destinations.length, 3);
  assert.equal(program.destinations[0].city, 'Cairo');
  assert.equal(program.segments.length, 4);
  assert.equal(program.stays[0].hotel_id, 'STH-000003');
  assert.equal(issues.errors.length, 0);
}

{
  const row = makeUmrah219();
  row[29] = true;
  const issues = {errors: [], warnings: []};
  assert.equal(context.stTdeBuildProgram_(row, row, 5, hotelIndex, issues), null);
}

console.log('ST_TDE_Exporter.gs: PASS');

{
  const row = makeUmrah219();
  row[4] = '13.11.2026';
  const issues = {errors: [], warnings: []};
  context.stTdeBuildProgram_(row, row, 6, hotelIndex, issues);
  assert.ok(issues.errors.some((issue) => String(issue.message || '').includes('Sayaç Hedef')));
}
