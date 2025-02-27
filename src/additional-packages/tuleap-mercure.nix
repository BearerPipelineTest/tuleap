{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {},
  nixpkgsPinEpoch ? (import ../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; }
}:

let
  tuleapMercureBin = pkgs.buildGoModule {
    name = "tuleap-mercure-bin";

    src = ./tuleap-mercure;

    vendorSha256 = "sha256-MgYCTR47ZwP1Mk02iWhr3isdoXUWtohA6bJoWugxwDw=";

    CGO_ENABLED=0;

     ldflags = [ "-s" "-w" ];
  };
  tuleapVersion = builtins.readFile ../../VERSION;
in pkgs.stdenvNoCC.mkDerivation {
  name = "tuleap-mercure-rpm-package";
  srcs = [
   "${tuleapMercureBin}/bin/tuleap-mercure"
  ./tuleap-mercure/Caddyfile
  ./tuleap-mercure/tuleap-mercure.service
  ];

  nativeBuildInputs = [ pkgs.rpm ];

  dontConfigure = true;

  unpackPhase = ''
    for srcFile in $srcs; do
      cp -a $srcFile $(stripHash $srcFile)
    done
  '';

  buildPhase = ''
    rpmbuild \
      --define "tuleap_version ${tuleapVersion}" \
      --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
      --define "_sourcedir $(pwd)" \
      --define "_rpmdir $(pwd)" \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "_rpmdir $(pwd)/RPMS" \
      --define "%_bindir /usr/bin" \
      --define "%_unitdir /usr/lib/systemd/system" \
      -bb ${./tuleap-mercure/tuleap-mercure.spec}
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/x86_64/*.rpm $out/
  '';

  dontFixUp = true;
}
