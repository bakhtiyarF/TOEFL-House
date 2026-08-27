#!/bin/bash
# Fresh database + seeded live data.
#
# The shipped DatabaseSeeder calls EnhancedSampleDataSeeder without first
# creating an organization/branch (which only SampleDataSeeder does), so the
# enhanced seeder bails out with "No branch found". This script runs the
# seeders in a working order instead.
set -e
export NODE_EXTRA_CA_CERTS=${NODE_EXTRA_CA_CERTS:-/usr/local/share/ca-certificates/e2b-ca.crt}
cd /home/user/TOEFL-House
ART="node tools/artisan.mjs"

echo "== migrate:fresh =="
$ART migrate:fresh --force

echo "== IamSeeder (roles + permissions) =="
$ART db:seed --class=IamSeeder --force

echo "== SampleDataSeeder (organization, campuses, branches, users, modules) =="
$ART db:seed --class=SampleDataSeeder --force

echo "== clearing SampleDataSeeder's students (they collide with the enhanced set's student_code) =="
$ART /home/user/TOEFL-House/tools/clear-students.php

echo "== EnhancedSampleDataSeeder (students, rosters, payments, certificates, donations, follow-ups) =="
$ART db:seed --class=EnhancedSampleDataSeeder --force

echo "== RuleEngineSeeder (rules, settings, notifications, audit log) =="
$ART db:seed --class=RuleEngineSeeder --force

echo "== done =="
