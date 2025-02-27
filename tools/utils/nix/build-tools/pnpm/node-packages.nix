# This file has been generated by node2nix 1.11.1. Do not edit!

{nodeEnv, fetchurl, fetchgit, nix-gitignore, stdenv, lib, globalBuildInputs ? []}:

let
  sources = {};
in
{
  "pnpm-^7" = nodeEnv.buildNodePackage {
    name = "pnpm";
    packageName = "pnpm";
    version = "7.9.3";
    src = fetchurl {
      url = "https://registry.npmjs.org/pnpm/-/pnpm-7.9.3.tgz";
      sha512 = "0hpAD1vtILw0i9gTN4ffagnScWMW9/avfZIKllBUd++fAvCss+hVgPPDd0HS9XcOT675gid4H9esGkbLdaFy+w==";
    };
    buildInputs = globalBuildInputs;
    meta = {
      description = "Fast, disk space efficient package manager";
      homepage = "https://pnpm.io";
      license = "MIT";
    };
    production = true;
    bypassCache = true;
    reconstructLock = true;
  };
}
