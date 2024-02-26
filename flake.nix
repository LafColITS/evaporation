{
  description = "Evaporation development environment";

  # Flake inputs
  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs?ref=nixos-unstable";
    phps.url = "github:fossar/nix-phps";
    flake-utils.url = "github:numtide/flake-utils";
  };

  # Flake outputs
  outputs = { self, nixpkgs, flake-utils, phps }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
        php74 = (phps.packages.${system}.php74.buildEnv {
          extensions = ({ enabled, all }: enabled ++ (with all; [
          ]));
        });
      in
      {
        devShell = pkgs.mkShell {
          buildInputs = [
            pkgs.nodejs_20
            pkgs.yarn
            php74
            php74.packages.composer
          ];
          shellHook = ''
            yarn
            php -v
          '';
        };
      }
    );
}